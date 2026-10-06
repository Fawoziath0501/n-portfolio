<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Référencement d'une page publique, calculé côté serveur à partir des données publiées :
 * titre, description, adresse canonique et équivalent dans l'autre langue (hreflang), fil d'Ariane,
 * données structurées schema.org et contenu HTML lisible sans JavaScript (moteurs, aperçus de partage).
 */
class SeoPage
{
    /** Pages du site : clé => [segment FR, segment EN, préfixe des textes d'en-tête]. */
    public const PAGES = [
        'about' => ['a-propos', 'about', 'pAbout'], 'work' => ['projets', 'work', 'pWork'], 'services' => ['services', 'services', 'pServices'],
        'blog' => ['blog', 'blog', 'pBlog'], 'contact' => ['contact', 'contact', 'pContact'],
    ];

    public bool $found = false;

    public string $type = 'home';

    public string $title = '';

    public string $description = '';

    /** @var array{fr: string, en: string} chemins de la page dans chaque langue */
    public array $alternates = ['fr' => '/fr', 'en' => '/en'];

    /** @var list<array{0: string, 1: string}> fil d'Ariane [libellé, chemin] (hors accueil) */
    public array $crumbs = [];

    /** Contenu lisible sans JavaScript : titre, introduction, HTML nettoyé, listes de liens. */
    public array $content = ['h1' => '', 'intro' => '', 'html' => '', 'links' => []];

    /** Données structurées propres à la page (article, étude de cas). */
    public ?array $item = null;

    /** Image propre à la page (couverture d'article), prioritaire pour les aperçus de partage. */
    public ?string $image = null;

    private array $d;

    public function __construct(private array $segments, public string $lang, array $public, public string $base)
    {
        $this->d = $public;
        $owner = trim(($public['profile']['firstName'] ?? '').' '.($public['profile']['lastName'] ?? ''));
        $this->title = $this->tx($public['seo']['siteTitle'] ?? '');
        $this->description = $this->tx($public['seo']['metaDescription'] ?? '');
        $this->resolve($owner);
    }

    private function resolve(string $owner): void
    {
        $seg = $this->segments;
        if ($seg === [] || ($seg[0] ?? '') !== 'fr' && ($seg[0] ?? '') !== 'en') {
            $this->found = $seg === [];
            $this->home();

            return;
        }
        [$s1, $s2] = [$seg[1] ?? null, $seg[2] ?? null];
        if ($s1 === null) {
            $this->found = true;
            $this->home();

            return;
        }
        if (count($seg) > 3) {
            return;
        }
        $pageKey = collect(self::PAGES)->search(fn ($p) => in_array($s1, [$p[0], $p[1]], true));
        $named = fn ($title) => trim($title.' | '.$owner, ' |');

        if ($pageKey && $s2 === null) {
            $this->page($pageKey);
            $this->title = $named($this->content['h1']);
        } elseif (in_array($s1, ['projets', 'work'], true) && $s2 !== null && ($p = $this->find('projects', 'slug', $s2))) {
            $this->project($p);
            $this->title = $named($this->content['h1']);
        } elseif ($s1 === 'blog' && $s2 !== null && ($p = $this->find('posts', 'slug', $s2))) {
            $this->post($p);
            $this->title = $named($this->content['h1']);
        } elseif ($s2 === null && ($p = collect($this->d['legalPages'] ?? [])->first(fn ($x) => in_array($s1, [$x['slugFr'], $x['slugEn']], true)))) {
            $this->legal($p);
            $this->title = $named($this->content['h1']);
        }
    }

    private function home(): void
    {
        $p = $this->d['profile'] ?? [];
        $this->type = 'home';
        $this->content['h1'] = trim(($p['firstName'] ?? '').' '.($p['middleName'] ?? '').' '.($p['lastName'] ?? ''));
        $this->content['intro'] = trim($this->tx($p['title'] ?? '').' — '.$this->tx($p['tagline'] ?? ''), ' —');
        $this->content['links'] = [
            $this->label('pWork', 1) => $this->projectLinks(),
            $this->label('pServices', 1) => array_map(fn ($s) => [$this->tx($s['title']), $this->path('services')], $this->d['services'] ?? []),
            $this->label('pBlog', 1) => $this->postLinks(),
        ];
    }

    private function page(string $key): void
    {
        $this->found = true;
        $this->type = $key;
        $this->alternates = ['fr' => '/fr/'.self::PAGES[$key][0], 'en' => '/en/'.self::PAGES[$key][1]];
        $this->content['h1'] = $this->label(self::PAGES[$key][2], 1);
        $this->content['intro'] = $this->label(self::PAGES[$key][2], 2);
        $this->description = $this->content['intro'] ?: $this->description;
        $this->crumbs = [[$this->content['h1'], $this->alternates[$this->lang]]];
        $p = $this->d['profile'] ?? [];

        match ($key) {
            'about' => $this->content = array_merge($this->content, [
                'html' => $this->rich($p['bio'] ?? ''),
                'links' => [$this->label('expTitle') ?: 'Expériences' => array_map(fn ($e) => [trim($this->tx($e['role']).' · '.($e['company'] ?? ''), ' ·'), null], $this->d['experiences'] ?? [])],
            ]),
            'work' => $this->content['links'] = [$this->content['h1'] => $this->projectLinks()],
            'services' => $this->content['links'] = [$this->content['h1'] => array_map(fn ($s) => [$this->tx($s['title']).' — '.Str::limit(self::plain($this->tx($s['description'])), 160), null], $this->d['services'] ?? [])],
            'blog' => $this->content['links'] = [$this->content['h1'] => $this->postLinks()],
            'contact' => $this->content['links'] = [$this->content['h1'] => array_values(array_filter([
                ! empty($p['email']) ? [$p['email'], 'mailto:'.$p['email']] : null,
                ! empty($p['phone']) ? [$p['phone'], 'tel:'.preg_replace('/\s+/', '', $p['phone'])] : null,
                $this->tx($p['location'] ?? '') ? [$this->tx($p['location']), null] : null,
            ]))],
        };
    }

    private function project(array $p): void
    {
        $this->found = true;
        $this->type = 'project';
        $this->alternates = ['fr' => '/fr/projets/'.$p['slug'], 'en' => '/en/work/'.$p['slug']];
        $this->content['h1'] = $this->tx($p['title']);
        $this->content['intro'] = $this->tx($p['summary']);
        $this->description = $this->content['intro'] ?: $this->description;
        $lines = fn ($v) => array_values(array_filter(array_map('trim', explode("\n", $this->tx($v)))));
        $list = fn ($v) => ($l = $lines($v)) ? '<ul>'.implode('', array_map(fn ($x) => '<li>'.e($x).'</li>', $l)).'</ul>' : '';
        $this->content['html'] = $this->rich($p['context'] ?? '').$this->rich($p['problem'] ?? '').$list($p['contribution'] ?? '').$list($p['solution'] ?? '').$this->rich($p['results'] ?? '');
        $this->crumbs = [[$this->label('pWork', 1), $this->path('work')], [$this->content['h1'], $this->alternates[$this->lang]]];
        $this->item = array_filter([
            '@type' => 'CreativeWork', 'name' => $this->content['h1'], 'description' => $this->content['intro'],
            'url' => $this->base.$this->alternates[$this->lang], 'genre' => $this->tx($p['category'] ?? ''),
            'keywords' => implode(', ', $p['tech'] ?? []), 'dateCreated' => $p['year'] ?: null,
            'creator' => ['@id' => $this->base.'/#person'], 'inLanguage' => $this->lang,
            'image' => $this->absolute(($p['images'] ?? [])[0] ?? ''),
            'sameAs' => $p['link'] ?: null,
        ]);
    }

    private function post(array $p): void
    {
        $this->found = true;
        $this->type = 'post';
        $this->image = $this->absolute($p['cover'] ?? '');
        $this->alternates = ['fr' => '/fr/blog/'.$p['slug'], 'en' => '/en/blog/'.$p['slug']];
        $this->content['h1'] = $this->tx($p['title']);
        $this->content['intro'] = $this->tx($p['excerpt'] ?? '');
        $this->content['html'] = $this->rich($p['body'] ?? '');
        $this->description = $this->content['intro'] ?: (self::excerpt($this->content['html']) ?: $this->description);
        $this->crumbs = [[$this->label('pBlog', 1), $this->path('blog')], [$this->content['h1'], $this->alternates[$this->lang]]];
        $this->item = array_filter([
            '@type' => 'BlogPosting', 'headline' => $this->content['h1'], 'description' => $this->description,
            'datePublished' => $p['date'] ?? null, 'inLanguage' => $this->lang,
            'url' => $this->base.$this->alternates[$this->lang], 'mainEntityOfPage' => $this->base.$this->alternates[$this->lang],
            'author' => ['@id' => $this->base.'/#person'], 'publisher' => ['@id' => $this->base.'/#person'],
            'keywords' => implode(', ', array_map(fn ($t) => $this->tx($t), $p['tags'] ?? [])),
            'wordCount' => str_word_count(self::plain($this->content['html'])) ?: null,
            'image' => $this->image,
        ]);
    }

    private function legal(array $p): void
    {
        $this->found = true;
        $this->type = 'legal';
        $this->alternates = ['fr' => '/fr/'.$p['slugFr'], 'en' => '/en/'.$p['slugEn']];
        $this->content['h1'] = $this->tx($p['title']);
        $prof = $this->d['profile'] ?? [];
        $this->content['html'] = str_replace(['{name}', '{email}', '{site}'], [
            e(trim(($prof['firstName'] ?? '').' '.($prof['middleName'] ?? '').' '.($prof['lastName'] ?? ''))),
            ! empty($prof['email']) ? '<a href="mailto:'.e($prof['email']).'">'.e($prof['email']).'</a>' : '',
            e($this->d['settings']['siteName'] ?? ''),
        ], $this->rich($p['body'] ?? ''));
        $this->description = self::excerpt($this->content['html']) ?: $this->description;
        $this->crumbs = [[$this->content['h1'], $this->alternates[$this->lang]]];
    }

    /** Graphe schema.org : site, personne, page courante et fil d'Ariane. */
    public function schema(): array
    {
        $p = $this->d['profile'] ?? [];
        $name = trim(($p['firstName'] ?? '').' '.($p['middleName'] ?? '').' '.($p['lastName'] ?? ''));
        $current = collect($this->d['experiences'] ?? [])->firstWhere('current', true);
        $skills = collect($this->d['skillGroups'] ?? [])->flatMap(fn ($g) => $g['skills'] ?? [])->map(fn ($s) => $this->tx($s['name']))->filter()->values()->all();
        $graph = [
            ['@type' => 'WebSite', '@id' => $this->base.'/#website', 'url' => $this->base.'/', 'name' => $this->d['settings']['siteName'] ?? $name,
                'inLanguage' => ['fr', 'en'], 'publisher' => ['@id' => $this->base.'/#person']],
            array_filter(['@type' => 'Person', '@id' => $this->base.'/#person', 'name' => $name, 'url' => $this->base.'/',
                'jobTitle' => $this->tx($p['title'] ?? ''), 'description' => $this->tx($p['tagline'] ?? ''),
                'email' => ! empty($p['email']) ? 'mailto:'.$p['email'] : null, 'telephone' => $p['phone'] ?? null,
                'image' => $this->absolute($p['photo'] ?? ''),
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $this->tx($p['location'] ?? '')],
                'worksFor' => $current && ! empty($current['company']) ? ['@type' => 'Organization', 'name' => $current['company']] : null,
                'knowsAbout' => $skills ?: null,
                'knowsLanguage' => array_map(fn ($l) => $this->tx($l['name']), $p['languages'] ?? []) ?: null,
                'sameAs' => collect($p['socials'] ?? [])->filter(fn ($s) => ($s['visible'] ?? false) && ! empty($s['url']))->pluck('url')->values()->all() ?: null]),
        ];
        if ($this->item) {
            $graph[] = $this->item;
        }
        if ($this->crumbs) {
            $items = array_merge([[$this->label('nav0') ?: 'Accueil', '/'.$this->lang]], $this->crumbs);
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($c, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $this->base.$c[1],
            ], $items, array_keys($items))];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /** Liens de la barre du haut (menus de l'administration), pour la version lisible sans JavaScript. */
    public function navLinks(): array
    {
        $routes = ['home' => '/'.$this->lang] + array_map(fn ($p) => '/'.$this->lang.'/'.($this->lang === 'en' ? $p[1] : $p[0]), self::PAGES);

        return collect($this->d['menus']['header']['items'] ?? [])->filter(fn ($i) => $i['visible'] ?? false)
            ->map(fn ($i) => [$this->tx($i['label'] ?? ''), $i['page'] === 'url' ? ($i['url'] ?: null) : ($routes[$i['page']] ?? $routes['about'].'#'.$i['page'])])
            ->filter(fn ($l) => $l[0] !== '' && $l[1])->values()->all();
    }

    private function projectLinks(): array
    {
        return array_map(fn ($p) => [$this->tx($p['title']).' — '.$this->tx($p['summary']), ($this->lang === 'en' ? '/en/work/' : '/fr/projets/').$p['slug']], $this->d['projects'] ?? []);
    }

    private function postLinks(): array
    {
        return array_map(fn ($p) => [$this->tx($p['title']), '/'.$this->lang.'/blog/'.$p['slug']], $this->d['posts'] ?? []);
    }

    private function find(string $collection, string $field, string $value): ?array
    {
        return collect($this->d[$collection] ?? [])->firstWhere($field, $value);
    }

    private function path(string $page): string
    {
        return '/'.$this->lang.'/'.self::PAGES[$page][$this->lang === 'en' ? 1 : 0];
    }

    private function label(string $key, ?int $i = null): string
    {
        return (string) ($this->d['labels'][$i === null ? $key : $key.'.'.$i][$this->lang] ?? '');
    }

    private function tx(mixed $v): string
    {
        return Portfolio::tx($v, $this->lang);
    }

    /** Texte enrichi : HTML déjà nettoyé à l'enregistrement, ou ancien texte brut converti en paragraphes. */
    private function rich(mixed $v): string
    {
        $s = trim($this->tx($v));

        return $s === '' ? '' : (RichText::isHtml($s) ? $s : RichText::fromPlain($s));
    }


    /** Texte brut d'un contenu HTML (blocs séparés par une espace, entités décodées). */
    public static function plain(string $html): string
    {
        $text = strip_tags(preg_replace('#</(p|li|h[1-6]|blockquote)>|<br\s*/?>#i', '$0 ', $html));

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** Extrait pour la méta-description : ~155 caractères, coupé entre deux mots. */
    public static function excerpt(string $html, int $max = 155): string
    {
        $text = self::plain($html);
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max);

        return rtrim(mb_substr($cut, 0, (int) mb_strrpos($cut, ' ')), " .,;:!?—-").'…';
    }
    private function absolute(string $url): ?string
    {
        return $url === '' ? null : (str_starts_with($url, 'http') ? $url : $this->base.'/'.ltrim($url, '/'));
    }
}
