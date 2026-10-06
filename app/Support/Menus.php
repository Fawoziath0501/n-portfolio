<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Menus du site public, éditables dans l'administration (réglage « menus ») :
 * la barre du haut (liens + bouton d'action) et les colonnes de liens du pied de page.
 * Un lien pointe vers une page du site (PAGES) ou vers une adresse libre (page « url »).
 */
class Menus
{
    /** Pages proposées dans l'administration ; « url » = adresse libre. */
    public const PAGES = ['home', 'about', 'work', 'services', 'blog', 'contact', 'approach', 'experience', 'languages', 'skills', 'url'];

    /** Menus par défaut ; $label(clé) renvoie { fr, en } d'un texte de l'interface. */
    public static function defaults(callable $label): array
    {
        $item = fn (string $page, array $text) => ['id' => Str::random(8), 'page' => $page, 'url' => '', 'label' => $text, 'visible' => true];
        $header = [
            $item('home', $label('nav.0')), $item('about', $label('nav.1')), $item('work', $label('nav.2')),
            $item('services', $label('nav.3')), $item('contact', $label('nav.4')),
        ];

        return [
            'header' => [
                'items' => $header,
                'cta' => ['visible' => true, 'page' => 'contact', 'url' => '', 'label' => $label('ctaContact')],
            ],
            'footer' => [
                ['id' => Str::random(8), 'title' => $label('footNavTitle'), 'items' => array_map(fn ($i) => ['id' => Str::random(8)] + $i, $header)],
                ['id' => Str::random(8), 'title' => $label('resources'), 'items' => [
                    $item('skills', $label('skillsLabel')), $item('experience', $label('eduLabel')),
                    $item('work', $label('nav2')), $item('blog', $label('blogNav')),
                ]],
            ],
        ];
    }

    /** Nettoie les menus reçus de l'administration (pages connues, adresses sûres, textes bornés). */
    public static function clean(array $menus): array
    {
        $text = fn ($v) => ['fr' => Str::limit(trim((string) ($v['fr'] ?? '')), 60, ''), 'en' => Str::limit(trim((string) ($v['en'] ?? '')), 60, '')];
        $link = fn ($i) => [
            'id' => preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($i['id'] ?? '')) ?: Str::random(8),
            'page' => in_array($i['page'] ?? '', self::PAGES, true) ? $i['page'] : 'home',
            'url' => self::safeUrl($i['url'] ?? ''),
            'label' => $text($i['label'] ?? []),
            'visible' => (bool) ($i['visible'] ?? true),
        ];
        $items = fn ($list) => array_values(array_map($link, array_slice(is_array($list) ? $list : [], 0, 12)));
        $cta = $menus['header']['cta'] ?? [];

        return [
            'header' => [
                'items' => $items($menus['header']['items'] ?? []),
                'cta' => ['visible' => (bool) ($cta['visible'] ?? true)] + array_diff_key($link($cta), ['id' => 1]),
            ],
            'footer' => array_values(array_map(fn ($c) => [
                'id' => preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($c['id'] ?? '')) ?: Str::random(8),
                'title' => $text($c['title'] ?? []),
                'items' => $items($c['items'] ?? []),
            ], array_slice(is_array($menus['footer'] ?? null) ? $menus['footer'] : [], 0, 4))),
        ];
    }

    /** Adresse libre : https, http, mailto, tel ou chemin interne ; tout le reste est refusé. */
    private static function safeUrl(mixed $url): string
    {
        $url = trim((string) $url);

        return preg_match('#^(https?://|mailto:|tel:|/(?!/))#i', $url) ? Str::limit($url, 500, '') : '';
    }
}
