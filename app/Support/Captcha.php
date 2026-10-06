<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Protection anti-spam des formulaires, réglée dans l'administration : Cloudflare Turnstile, Google reCAPTCHA (case
 * à cocher) ou hCaptcha, activable formulaire par formulaire. La clé secrète est chiffrée et jamais renvoyée au navigateur.
 * Un champ piège invisible (« website ») complète le dispositif sans rien demander aux visiteurs.
 */
class Captcha
{
    private const KEY = 'captcha';

    public const FORMS = ['contact', 'service', 'newsletter', 'login'];

    private const VERIFY = [
        'turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        'recaptcha' => 'https://www.google.com/recaptcha/api/siteverify',
        'hcaptcha' => 'https://api.hcaptcha.com/siteverify',
    ];

    /** Réglages bruts (clé secrète chiffrée). */
    public static function get(): array
    {
        $s = Setting::get(self::KEY) + ['provider' => 'none', 'siteKey' => '', 'secret' => '', 'forms' => []];
        $s['forms'] = array_merge(array_fill_keys(self::FORMS, true), (array) $s['forms']);

        return $s;
    }

    /** Pour l'administration : tout sauf la clé secrète. */
    public static function forAdmin(): array
    {
        $s = self::get();
        $s['hasSecret'] = $s['secret'] !== '';
        unset($s['secret']);

        return $s;
    }

    /** Pour le site public : fournisseur, clé publique et formulaires protégés (rien si désactivé). */
    public static function forPublic(): ?array
    {
        if (! self::active()) {
            return null;
        }
        $s = self::get();

        return ['provider' => $s['provider'], 'siteKey' => $s['siteKey'], 'forms' => array_keys(array_filter($s['forms']))];
    }

    /** Enregistre ; une clé secrète vide conserve l'actuelle. */
    public static function save(array $v): void
    {
        $old = self::get();
        Setting::put(self::KEY, [
            'provider' => in_array($v['provider'] ?? 'none', ['none', ...array_keys(self::VERIFY)], true) ? $v['provider'] : 'none',
            'siteKey' => trim((string) ($v['siteKey'] ?? '')),
            'secret' => ($v['secret'] ?? '') !== '' ? Crypt::encryptString(trim($v['secret'])) : $old['secret'],
            'forms' => collect(self::FORMS)->mapWithKeys(fn ($k) => [$k => (bool) ($v['forms'][$k] ?? false)])->all(),
        ]);
    }

    public static function active(): bool
    {
        $s = self::get();

        return $s['provider'] !== 'none' && $s['siteKey'] !== '' && $s['secret'] !== '';
    }

    public static function protects(string $form): bool
    {
        return self::active() && ! empty(self::get()['forms'][$form]);
    }

    /**
     * Vérifie la requête d'un formulaire : champ piège, puis jeton du captcha si le formulaire est protégé.
     * Renvoie false pour un robot pris au piège (à ignorer silencieusement) ; lève une erreur de validation si le captcha échoue.
     */
    public static function check(Request $request, string $form): bool
    {
        if (filled($request->input('website'))) {
            return false;
        }
        if (! self::protects($form)) {
            return true;
        }
        $token = (string) $request->input('captcha', '');
        if ($token === '' || ! self::verify($token, $request->ip())) {
            throw ValidationException::withMessages(['captcha' => $request->input('lang') === 'en'
                ? 'Please confirm you are not a robot.'
                : 'Merci de confirmer que vous n’êtes pas un robot.']);
        }

        return true;
    }

    private static function verify(string $token, ?string $ip): bool
    {
        $s = self::get();
        try {
            $secret = Crypt::decryptString($s['secret']);
        } catch (\Throwable) {
            Log::warning('Captcha : clé secrète illisible (APP_KEY changée ?), à ressaisir dans l’administration.');

            return false;
        }
        try {
            $res = Http::asForm()->timeout(8)->post(self::VERIFY[$s['provider']], ['secret' => $secret, 'response' => $token, 'remoteip' => $ip]);

            return (bool) $res->json('success');
        } catch (\Throwable $e) {
            // Fournisseur injoignable : on laisse passer plutôt que de bloquer les vrais visiteurs (limite de débit toujours active).
            Log::warning('Captcha : vérification impossible : '.$e->getMessage());

            return true;
        }
    }
}
