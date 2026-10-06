<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\UiLabel;
use Tests\TestCase;

class BlogMenusTest extends TestCase
{
    public function test_last_name_is_uppercase(): void
    {
        $this->assertSame('SALOU', Profile::current()->last_name);
        $this->get('/fr')->assertSee('Fawoziath Modjissola SALOU | ', false);
    }

    public function test_posts_have_slugs_and_detail_pages(): void
    {
        $post = Post::published()->first();
        $this->assertNotEmpty($post->slug);

        $this->get("/fr/blog/{$post->slug}")->assertOk()->assertSee('<title>'.e($post->title['fr']).' | ', false);
        $this->get('/sitemap.xml')->assertSee("/fr/blog/{$post->slug}</loc>", false);
        $this->assertContains($post->slug, array_column($this->getJson('/api/site')->json('posts'), 'slug'));
    }

    public function test_post_views_are_counted_without_touching_updated_at(): void
    {
        $post = Post::published()->first();
        $updated = $post->updated_at;
        $this->travel(1)->hour();

        $this->postJson("/api/posts/{$post->slug}/view")->assertOk()->assertJson(['views' => 1]);
        $this->postJson("/api/posts/{$post->slug}/view")->assertOk()->assertJson(['views' => 2]);
        $this->postJson('/api/posts/inconnu/view')->assertNotFound();

        $this->assertEquals($updated, $post->fresh()->updated_at);
    }

    public function test_post_body_slug_and_reading_time(): void
    {
        $this->admin();
        $body = '<p>'.str_repeat('mot ', 500).'</p><script>x</script>';

        $res = $this->postJson('/api/admin/posts', ['item' => [
            'title' => ['fr' => 'Mon nouvel article !', 'en' => 'My new article'], 'slug' => '', 'views' => 999,
            'body' => ['fr' => $body, 'en' => ''], 'published' => true,
        ]])->assertCreated();

        $post = Post::findOrFail($res->json('id'));
        $this->assertSame('mon-nouvel-article', $post->slug);
        $this->assertSame(3, $post->read_min);
        $this->assertSame(0, (int) $post->views, 'le compteur ne se modifie pas depuis l’administration');
        $this->assertStringNotContainsString('<script>', $post->body['fr']);

        $this->postJson('/api/admin/posts', ['item' => ['title' => ['fr' => 'Mon nouvel article !'], 'slug' => '']])->assertCreated()
            ->assertJsonPath('slug', 'mon-nouvel-article-2');
        $this->postJson('/api/admin/posts', ['item' => ['title' => ['fr' => 'X'], 'slug' => 'mon-nouvel-article']])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_menus_are_editable_and_sanitized(): void
    {
        $menus = $this->admin()->getJson('/api/admin/data')->json('menus');
        $this->assertSame(['home', 'about', 'work', 'services', 'contact'], array_column($menus['header']['items'], 'page'));
        $this->assertCount(2, $menus['footer']);

        $menus['header']['items'][] = ['id' => 'x1', 'page' => 'blog', 'label' => ['fr' => 'Blog', 'en' => 'Blog'], 'visible' => true];
        $menus['header']['items'][] = ['id' => 'x2', 'page' => 'url', 'url' => 'javascript:alert(1)', 'label' => ['fr' => 'Piège', 'en' => 'Trap'], 'visible' => true];
        $menus['header']['items'][] = ['id' => 'x3', 'page' => 'inconnue', 'label' => ['fr' => 'A', 'en' => 'A']];
        $menus['header']['cta']['visible'] = false;
        $this->putJson('/api/admin/documents/menus', ['value' => $menus])->assertOk();

        $saved = Setting::get('menus');
        $this->assertSame('blog', $saved['header']['items'][5]['page']);
        $this->assertSame('', $saved['header']['items'][6]['url']);
        $this->assertSame('home', $saved['header']['items'][7]['page']);
        $this->assertFalse($saved['header']['cta']['visible']);
        $this->assertSame($saved, $this->getJson('/api/site')->json('menus'));
    }

    public function test_menu_labels_left_text_settings(): void
    {
        $this->assertSame(0, UiLabel::whereIn('key', ['nav.0', 'footNavTitle', 'resources'])->count());
        $this->assertSame(1, UiLabel::where('key', 'sortPopular')->count());
    }
}
