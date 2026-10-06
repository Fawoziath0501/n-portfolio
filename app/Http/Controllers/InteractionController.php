<?php

namespace App\Http\Controllers;

use App\Mail\NewMessageMail;
use App\Models\Event;
use App\Models\Message;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Support\MailSettings;
use Illuminate\Http\Request;
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
            'phone' => 'nullable|string|max:40',
            'subject' => 'nullable|string|max:200',
            'service' => 'nullable|string|max:200',
            'when' => 'nullable|string|max:80',
            'message' => 'required|string|min:10|max:5000',
            'lang' => 'nullable|in:fr,en',
        ]);

        $isService = ($v['type'] ?? 'contact') === 'service';
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
        if (($settings['notifyOnMessage'] ?? false) && ! empty($settings['notifyEmail'])) {
            try {
                MailSettings::apply(); // serveur SMTP réglé dans l'administration
                Mail::to($settings['notifyEmail'])->send(new NewMessageMail($message));
            } catch (\Throwable $e) {
                Log::warning('Notification e-mail non envoyée : '.$e->getMessage());
            }
        }

        return response()->json(['ok' => true], 201);
    }

    public function subscribe(Request $request)
    {
        $v = $request->validate(['email' => 'required|email|max:180', 'lang' => 'nullable|in:fr,en']);
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

        return response()->json(['ok' => true], 201);
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
