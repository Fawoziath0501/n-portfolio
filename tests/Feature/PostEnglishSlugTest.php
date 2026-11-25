<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

/** Adresse anglaise propre à chaque article ; les anciens liens anglais (slug français) redirigent vers elle. */
class PostEnglishSlugTest extends TestCase
{
    private function article(): Post
    {
        return Post::firstWhere('slug', 'laravel-et-vuejs-pourquoi-ce-duo-est-ideal-pour-vos-projets-web');
    }

    public function test_english_slug_is_generated_from_the_english_title(): void
    {
        $this->assertSame('laravel-and-vuejs-why-this-pairing-is-ideal-for-your-web-projects', $this->article()->slug_en);
        $this->assertSame('/en/blog/laravel-and-vuejs-why-this-pairing-is-ideal-for-your-web-projects', $this->article()->path('en'));
        $this->assertSame('/fr/blog/laravel-et-vuejs-pourquoi-ce-duo-est-ideal-pour-vos-projets-web', $this->article()->path('fr'));
    }

    public function test_each_language_has_its_own_address_and_old_links_redirect(): void
    {
        $post = $this->article();
        $en = '/en/blog/'.$post->slug_en;

        $this->get($en)->assertOk()
            ->assertSee('<link rel="canonical" href="'.url($en).'">', false)
            ->assertSee('hreflang="fr" href="'.url('/fr/blog/'.$post->slug).'"', false);

        // Lien déjà partagé : /en/blog/{slug français} → nouvelle adresse anglaise, paramètres conservés.
        $this->get('/en/blog/'.$post->slug.'?utm_source=linkedin')->assertStatus(301)->assertRedirect($en.'?utm_source=linkedin');
        $this->get('/fr/blog/'.$post->slug_en)->assertStatus(301)->assertRedirect('/fr/blog/'.$post->slug);

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString('<loc>'.url($en).'</loc>', $sitemap);
        $this->assertStringNotContainsString('<loc>'.url('/en/blog/'.$post->slug).'</loc>', $sitemap);
    }

    public function test_views_are_counted_from_either_address(): void
    {
        $post = $this->article();
        $this->postJson('/api/posts/'.$post->slug_en.'/view')->assertOk();
        $this->postJson('/api/posts/'.$post->slug.'/view')->assertOk();
        $this->assertSame(2 + (int) $post->views, $post->fresh()->views);
    }

    public function test_english_address_must_be_unique_across_both_languages(): void
    {
        $other = Post::where('id', '!=', $this->article()->id)->first();
        $this->admin()->putJson('/api/admin/posts/'.$other->id, ['item' => ['slugEn' => $this->article()->slug]])
            ->assertStatus(422)->assertJsonValidationErrors('slugEn');
    }

    public function test_long_titles_drop_the_name_suffix(): void
    {
        $post = $this->article();
        $this->get('/en/blog/'.$post->slug_en)->assertSee('<title>Laravel and Vue.js: why this pairing is ideal for your web projects</title>', false);
        $this->get('/fr/projets/ciste')->assertSee('<title>CISTE | ', false);
    }
}
