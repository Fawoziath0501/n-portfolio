<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\Captcha;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Page de dépôt de témoignage (lien envoyé par l'administratrice à ses clients et collègues, non référencée).
 * Le témoignage est enregistré en brouillon : il n'est publié qu'après relecture dans l'administration.
 */
class TestimonialSubmitController extends Controller
{
    public function form(string $lang)
    {
        return response()->view('testimonial-form', $this->viewData($lang));
    }

    public function store(Request $request, string $lang)
    {
        app()->setLocale($lang); // messages d’erreur dans la langue de la page
        $en = $lang === 'en';
        $v = $request->validate([
            'name' => 'required|string|max:120',
            'role' => 'nullable|string|max:120',
            'company' => 'nullable|string|max:120',
            'project_id' => 'nullable|integer|exists:projects,id',
            'email' => 'required|email|max:180',
            'quote' => 'required|string|min:30|max:1500',
            'consent' => 'accepted',
        ], [
            'consent.accepted' => $en ? 'Please confirm that your testimonial may be published.' : 'Merci de confirmer que votre témoignage peut être publié.',
            'quote.min' => $en ? 'Your testimonial needs at least 30 characters.' : 'Votre témoignage doit contenir au moins 30 caractères.',
        ]);
        if (! Captcha::check($request->merge(['lang' => $lang]), 'contact')) {
            return redirect()->route('testimonial.thanks.'.$lang); // robot pris au piège : rien n'est enregistré
        }

        $other = $en ? 'fr' : 'en';
        $t = Testimonial::create([
            'name' => trim($v['name']),
            'company' => trim($v['company'] ?? ''),
            'role' => [$lang => trim($v['role'] ?? ''), $other => ''],
            'quote' => [$lang => trim($v['quote']), $other => ''],
            'project_id' => $v['project_id'] ?? null,
            'published' => false,
            'position' => (int) Testimonial::max('position') + 1,
        ]);
        Activity::log('Nouveau témoignage reçu de '.$t->name.' (en attente de relecture)');

        $settings = Setting::get('settings');
        if (! empty($settings['notifyEmail'])) {
            try {
                MailSettings::apply();
                $body = "Nouveau témoignage déposé sur votre portfolio (en brouillon, à relire avant publication).\n\n"
                    .'Nom : '.$t->name."\n".'Fonction : '.($v['role'] ?? '—')."\n".'Entreprise : '.($t->company ?: '—')."\n"
                    .'E-mail (non publié) : '.$v['email']."\n".'Langue : '.strtoupper($lang)."\n\n« ".trim($v['quote'])." »\n\n"
                    .'Relire et publier : '.url('/'.config('portfolio.admin.path').'/testimonials');
                Mail::raw($body, fn ($m) => $m->to($settings['notifyEmail'])->replyTo($v['email'], $t->name)->subject('Nouveau témoignage · '.$t->name));
            } catch (\Throwable $e) {
                Log::warning('Notification de témoignage non envoyée : '.$e->getMessage());
            }
        }

        return redirect()->route('testimonial.thanks.'.$lang);
    }

    public function thanks(string $lang)
    {
        return response()->view('testimonial-form', $this->viewData($lang) + ['thanks' => true]);
    }

    private function viewData(string $lang): array
    {
        $p = Profile::query()->first();

        return [
            'lang' => $lang,
            'owner' => trim(($p?->first_name ?? '').' '.($p?->last_name ?? '')),
            'projects' => Project::query()->published()->ordered()->get(['id', 'title'])->map(fn ($x) => ['id' => $x->id, 'title' => $x->title[$lang] ?? $x->title['fr'] ?? ''])->all(),
            'captcha' => Captcha::protects('contact') ? Captcha::forPublic() : null,
            'action' => route('testimonial.store.'.$lang),
            'site' => url('/'.$lang),
        ];
    }
}
