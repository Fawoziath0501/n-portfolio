<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\Post;
use App\Models\Service;
use App\Support\SiteIcons;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class SeoAuditTest extends TestCase
{
    public function test_hreflang_points_to_the_equivalent_page(): void
    {
        $this->get('/fr/a-propos')
            ->assertSee('<link rel="canonical" href="http://localhost/fr/a-propos">', false)
            ->assertSee('hreflang="en" href="http://localhost/en/about"', false)
            ->assertSee('hreflang="x-default" href="http://localhost/fr/a-propos"', false);

        $this->get('/en/work/presentia')->assertSee('hreflang="fr" href="http://localhost/fr/projets/presentia"', false);

        $legal = LegalPage::where('key', 'terms')->first();
        $this->get('/en/'.$legal->slug_en)->assertSee('hreflang="fr" href="http://localhost/fr/'.$legal->slug_fr.'"', false);

        $post = tap(Post::query()->first())->update(['published' => true]); // articles d'exemple livrés en brouillon
        $this->get('/fr/blog/'.$post->slug)->assertSee('hreflang="en" href="http://localhost/en/blog/'.$post->slug.'"', false);
    }

    public function test_pages_are_readable_without_javascript(): void
    {
        $project = \App\Models\Project::where('slug', 'presentia')->first();

        // Accueil : nom, liens vers les études de cas et les articles, menu.
        $this->get('/fr')->assertSee('<h1>Fawoziath Modjissola SALOU</h1>', false)
            ->assertSee('href="/fr/projets/presentia"', false)->assertSee('href="/fr/a-propos"', false);
        // Étude de cas : titre, résumé et contenu dans le HTML initial.
        $this->get('/fr/projets/presentia')->assertSee('<h1>'.e($project->title['fr']).'</h1>', false)
            ->assertSee(e($project->summary['fr']), false)->assertSee('Rôles et permissions : création de rôles');
        // Page légale : variables remplacées.
        $main = explode('<main', $this->get('/fr/confidentialite')->getContent())[1];
        $this->assertStringContainsString('saloufawoziath236@gmail.com', $main);
        $this->assertStringNotContainsString('{email}', explode('</main>', $main)[0]);
    }

    public function test_structured_data_and_share_tags(): void
    {
        $post = tap(Post::query()->first())->update(['published' => true]); // articles d'exemple livrés en brouillon
        $html = $this->get('/fr/blog/'.$post->slug)->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $types = array_column(json_decode($m[1], true)['@graph'], '@type');
        $this->assertSame(['WebSite', 'Person', 'BlogPosting', 'BreadcrumbList'], $types);
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="http://localhost/fr/blog/'.$post->slug.'">', $html);
        $this->assertStringContainsString('<meta name="twitter:card"', $html);

        $html = $this->get('/fr/projets/presentia')->getContent();
        $this->assertStringContainsString('"@type":"CreativeWork"', $html);
    }

    public function test_search_engine_verification_tags(): void
    {
        \App\Models\Setting::put('seo', ['googleVerification' => 'abc123', 'bingVerification' => 'XYZ'] + \App\Models\Setting::get('seo'));

        $this->get('/fr')->assertSee('<meta name="google-site-verification" content="abc123">', false)
            ->assertSee('<meta name="msvalidate.01" content="XYZ">', false);
    }

    public function test_unknown_addresses_return_404_without_indexing(): void
    {
        foreach (['/fr/page-inconnue', '/fr/projets/inconnu', '/en/blog/inconnu', '/de', '/fr/a-propos/trop/long', '/fr/mentions-legales'] as $path) {
            $this->get($path)->assertNotFound()->assertSee('noindex', false)->assertDontSee('rel="canonical"', false);
        }
        $this->get('/')->assertOk();
        $this->get('/en')->assertOk();
    }

    public function test_only_used_icons_are_requested_from_google_fonts(): void
    {
        Service::first()->update(['icon' => 'rocket_launch']);

        $names = SiteIcons::names();
        $this->assertContains('rocket_launch', $names, 'icône choisie dans l’administration');
        $this->assertSame($names, collect($names)->sort()->values()->all(), 'liste triée (exigence de Google Fonts)');
        $this->get('/fr')->assertSee('&amp;icon_names='.implode(',', $names), false);
    }

    public function test_every_icon_written_in_site_components_is_declared(): void
    {
        $used = [];
        foreach ((new Finder)->files()->in([resource_path('js/site'), resource_path('js/shared')])->name(['*.vue', '*.js']) as $file) {
            $src = $file->getContents();
            preg_match_all('/class="[^"]*\bms\b[^"]*"[^>]*>([a-z0-9_]+)</', $src, $a);
            preg_match_all("/icon: (?:[^,}]*\\|\\| )?'([a-z0-9_]+)'/", $src, $b);
            $used = array_merge($used, $a[1], $b[1]);
        }

        $this->assertSame([], array_values(array_diff(array_unique($used), SiteIcons::STATIC)), 'icônes à ajouter à SiteIcons::STATIC');
    }
}
