<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use App\Mail\AckMail;
use App\Mail\NewMessageMail;
use App\Mail\WelcomeSubscriberMail;
use App\Models\Event;
use App\Models\Message;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Interactions des visiteurs : contact, demandes de service, newsletter, suivi d'audience. */
class InteractionController extends Controller
{
    public function message(Request $request)
    {
        $v = $request->validate([
            'type' => 'nullable|in:contact,service',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:180',
            'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9 ().-]{6,}[0-9]$/'],
            'subject' => 'nullable|string|max:200',
            'service' => 'nullable|string|max:200',
            'when' => 'nullable|string|max:80',
            'message' => 'required|string|min:10|max:5000',
            'lang' => 'nullable|in:fr,en',
        ], [
            'phone.required' => $request->input('lang') === 'en' ? 'Please enter your phone or WhatsApp number.' : 'Indiquez votre numéro de téléphone ou WhatsApp.',
            'phone.regex' => $request->input('lang') === 'en' ? 'Please enter a valid phone number, with country code (e.g. +229).' : 'Indiquez un numéro valide, avec l’indicatif (ex. +229).',
        ]);

        $isService = ($v['type'] ?? 'contact') === 'service';
        if (! Captcha::check($request, $isService ? 'service' : 'contact')) {
            return response()->json(['ok' => true], 201); // robot pris au piège : réponse normale, rien d'enregistré
        }
        $body = trim($v['message']);
        if ($isService) {
            $body = implode("\n\n", array_filter([
                $body,
                ! empty($v['phone']) ? 'Tél. / WhatsApp : '.$v['phone'] : null,
                ! empty($v['when']) ? 'Délai : '.$v['when'] : null,
            ]));
        }

        $service = $isService ? Service::published()->get()->first(fn ($x) => in_array($v['service'] ?? '', [$x->title['fr'] ?? '', $x->title['en'] ?? ''], true)) : null;

        $message = Message::create([
            'type' => $isService ? 'service' : 'contact',
            'service_id' => $service?->id,
            'service' => $v['service'] ?? '',
            'name' => trim($v['name']),
            'email' => trim($v['email']),
            'phone' => trim($v['phone'] ?? ''),
            'subject' => $isService ? ($v['service'] ?? '') : ($v['subject'] ?? ''),
            'body' => $body,
            'lang' => $v['lang'] ?? 'fr',
        ]);

        $settings = Setting::get('settings');
        MailSettings::apply(); // serveur SMTP réglé dans l'administration
        if (($settings['notifyOnMessage'] ?? false) && ! empty($settings['notifyEmail'])) {
            $this->sendQuietly($settings['notifyEmail'], new NewMessageMail($message), 'Notification');
        }
        // Accusé de réception au visiteur (activé par défaut).
        if ($settings['autoReply'] ?? true) {
            $this->sendQuietly($message->email, new AckMail($message), 'Accusé de réception');
        }

        return response()->json(['ok' => true], 201);
    }

    public function subscribe(Request $request)
    {
        $v = $request->validate(['email' => 'required|email|max:180', 'lang' => 'nullable|in:fr,en']);
        if (! Captcha::check($request, 'newsletter')) {
            return response()->json(['ok' => true], 201);
        }
        $email = strtolower(trim($v['email']));

        $existing = Subscriber::withTrashed()->where('email', $email)->first();
        if ($existing && ! $existing->trashed()) {
            return response()->json(['error' => 'dup'], 409);
        }
        if ($existing) {
            // Inscrit retiré qui revient : on restaure plutôt que de dupliquer.
            $existing->restore();
            $existing->update(['lang' => $v['lang'] ?? $existing->lang]);
        } else {
            Subscriber::create(['email' => $email, 'lang' => $v['lang'] ?? 'fr']);
        }

        // Confirmation d'inscription (activée par défaut).
        if (Setting::get('settings')['welcomeSubscriber'] ?? true) {
            MailSettings::apply();
            $this->sendQuietly($email, new WelcomeSubscriberMail($v['lang'] ?? 'fr'), 'Confirmation d’inscription');
        }

        return response()->json(['ok' => true], 201);
    }

    /** Envoi d'un e-mail sans jamais faire échouer le formulaire (l'erreur est notée dans les journaux). */
    private function sendQuietly(string $to, Mailable $mail, string $what): void
    {
        try {
            Mail::to($to)->send($mail);
        } catch (\Throwable $e) {
            Log::warning($what.' non envoyé(e) à '.$to.' : '.$e->getMessage());
        }
    }

    /** Lecture d'un article (une fois par visite, dédoublonnée côté navigateur). */
    public function postView(string $slug)
    {
        $query = Post::published()->where('slug', $slug);
        abort_unless($query->exists(), 404);
        $query->toBase()->increment('views'); // sans toucher à updated_at

        return response()->json(['views' => (int) $query->value('views')]);
    }

    public function track(Request $request)
    {
        $settings = Setting::get('settings');
        if (($settings['analytics']['provider'] ?? '') !== 'local') {
            return response()->noContent();
        }

        $v = $request->validate([
            't' => 'required|in:pv,click',
            'k' => 'nullable|string|max:30',
            'path' => 'nullable|string|max:255',
            'ref' => 'nullable|string|max:255',
            'dev' => 'nullable|in:mobile,desktop,tablet',
            'sid' => 'required|string|max:40',
        ]);

        // Le référent n'est gardé que s'il vient d'un autre site.
        $ref = $v['ref'] ?? '';
        $host = parse_url($ref, PHP_URL_HOST);
        $ref = $host && $host !== $request->getHost() ? strtolower($host) : null;

        Event::create([
            'type' => $v['t'], 'key' => $v['k'] ?? null, 'path' => $v['path'] ?? null,
            'ref' => $ref, 'device' => $v['dev'] ?? 'desktop', 'sid' => $v['sid'],
        ]);

        return response()->noContent();
    }
}
