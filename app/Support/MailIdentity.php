<?php

namespace App\Support;

use App\Models\Profile;

/** Signature des e-mails envoyés aux visiteurs : identité, coordonnées et liens du site, dans la langue du destinataire. */
class MailIdentity
{
    public static function for(string $lang): array
    {
        $lang = $lang === 'en' ? 'en' : 'fr';
        $p = Profile::query()->first()?->toFront() ?? [];
        $tx = fn ($v) => is_array($v) ? ($v[$lang] ?? $v['fr'] ?? '') : (string) $v;
        $social = fn ($label) => collect($p['socials'] ?? [])->first(fn ($s) => ($s['visible'] ?? true) && strcasecmp($s['label'] ?? '', $label) === 0);
        $wa = $social('WhatsApp');
        $site = rtrim((string) config('app.url'), '/');

        return [
            'lang' => $lang,
            'name' => trim(implode(' ', array_filter([$p['firstName'] ?? '', $p['middleName'] ?? '', $p['lastName'] ?? '']))) ?: (string) config('app.name'),
            'shortName' => trim(($p['firstName'] ?? '').' '.($p['lastName'] ?? '')) ?: (string) config('app.name'),
            'title' => $tx($p['title'] ?? ''),
            'location' => $tx($p['location'] ?? ''),
            'email' => $p['email'] ?? '',
            'phone' => $p['phone'] ?? '',
            'whatsapp' => $wa ? ['label' => $wa['handle'] ?: 'WhatsApp', 'url' => $wa['url']] : null,
            'linkedin' => $social('LinkedIn')['url'] ?? null,
            'replyDelay' => self::delay($tx($p['replyDelay'] ?? ''), $lang),
            'site' => $site.'/'.$lang,
            'host' => parse_url($site, PHP_URL_HOST) ?: $site,
            'projects' => $site.($lang === 'en' ? '/en/work' : '/fr/projets'),
            'blog' => $site.'/'.$lang.'/blog',
        ];
    }

    /** Délai de réponse dans une phrase : « sous 24 h en général » (FR), « within 24 hours » (EN). */
    private static function delay(string $v, string $lang): string
    {
        if ($lang === 'en') {
            return preg_match('/(\d+)\s*h/i', $v, $m) ? 'within '.$m[1].' hours' : ($v ?: 'within 24 hours');
        }

        return $v ?: '24 h';
    }

    /** Nom du visiteur pour la formule d'appel ; écarté s'il ressemble à un lien ou à du texte publicitaire. */
    public static function safeName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' || mb_strlen($name) > 60 || preg_match('#https?://|www\.|@|\.(com|net|org|xyz|ru|info)\b#i', $name) ? null : $name;
    }
}
