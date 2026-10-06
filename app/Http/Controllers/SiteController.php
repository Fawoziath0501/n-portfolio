<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Support\Portfolio;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /** Page publique (SPA Vue) avec métadonnées SEO rendues côté serveur. */
    public function show(Request $request)
    {
        $segments = $request->segments();
        $lang = ($segments[0] ?? 'fr') === 'en' ? 'en' : 'fr';
        $seo = Setting::get('seo');
        $data = Portfolio::public();

        $title = Portfolio::tx($seo['siteTitle'] ?? '', $lang);
        $description = Portfolio::tx($seo['metaDescription'] ?? '', $lang);

        $maintenance = $data['settings']['maintenance'];

        if (! $maintenance && in_array($segments[1] ?? '', ['projets', 'work'], true) && isset($segments[2])) {
            $project = Project::published()->where('slug', $segments[2])->first();
            if ($project) {
                $title = Portfolio::tx($project->title, $lang).' | '.trim(($data['profile']['firstName'] ?? '').' '.($data['profile']['lastName'] ?? ''));
                $description = Portfolio::tx($project->summary, $lang);
            }
        }

        return response()->view('site', [
            'lang' => $lang,
            'title' => $title,
            'description' => $description,
            'seo' => $seo,
            'profile' => $data['profile'],
            'data' => $data,
            'maintenance' => $maintenance,
        ], $maintenance ? 503 : 200, $maintenance ? ['Retry-After' => 3600] : []);
    }

    public function data()
    {
        return response()->json(Portfolio::public());
    }

    /** Plan du site : pages et études de cas publiées, en français et en anglais (hreflang). */
    public function sitemap()
    {
        abort_unless($this->indexable(), 404);

        $base = $this->baseUrl();
        $pages = [['', '']];
        foreach (['a-propos' => 'about', 'projets' => 'work', 'services' => 'services', 'blog' => 'blog', 'contact' => 'contact'] as $fr => $en) {
            $pages[] = ['/'.$fr, '/'.$en];
        }
        foreach (Project::published()->ordered()->get(['slug', 'updated_at']) as $p) {
            $pages[] = ['/projets/'.$p->slug, '/work/'.$p->slug, $p->updated_at];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($pages as $page) {
            $urls = ['fr' => $base.'/fr'.$page[0], 'en' => $base.'/en'.$page[1]];
            foreach ($urls as $loc) {
                $xml .= '  <url><loc>'.e($loc).'</loc>'
                    .(isset($page[2]) ? '<lastmod>'.$page[2]->toDateString().'</lastmod>' : '');
                foreach ($urls as $lang => $alt) {
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$lang.'" href="'.e($alt).'"/>';
                }
                $xml .= "</url>\n";
            }
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $body = $this->indexable()
            ? "User-agent: *\nDisallow: /admin\nDisallow: /api/\n\nSitemap: ".$this->baseUrl()."/sitemap.xml\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Indexation autorisée dans « SEO & partage » et site hors maintenance. */
    private function indexable(): bool
    {
        return (Setting::get('seo')['indexable'] ?? true) !== false && ! Portfolio::maintenance();
    }

    /** Adresse canonique du site (réglage SEO) ou, à défaut, APP_URL. */
    private function baseUrl(): string
    {
        return rtrim(Setting::get('seo')['canonical'] ?? '', '/') ?: rtrim(url('/'), '/');
    }
}
