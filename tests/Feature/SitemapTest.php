<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Setting;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_lists_pages_and_published_projects_in_both_languages(): void
    {
        Setting::put('seo', ['canonical' => 'https://fawoziath.dev/'] + Setting::get('seo'));
        Project::where('slug', 'ciste')->update(['published' => false]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>https://fawoziath.dev/fr/a-propos</loc>', false)
            ->assertSee('<loc>https://fawoziath.dev/en/work/presentia</loc>', false)
            ->assertSee('hreflang="en" href="https://fawoziath.dev/en/about"', false)
            ->assertDontSee('ciste');
    }

    public function test_robots_points_to_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: http://localhost/sitemap.xml');
    }

    public function test_nothing_is_indexed_when_indexing_is_disabled(): void
    {
        Setting::put('seo', ['indexable' => false] + Setting::get('seo'));

        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
    }
}
