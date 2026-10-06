<?php

namespace Tests\Feature;

use App\Mail\PostNewsletterMail;
use App\Mail\WelcomeSubscriberMail;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(): Post
    {
        $post = Post::query()->firstOrFail();
        $post->update(['published' => true]);

        return $post;
    }

    public function test_admin_sends_an_article_to_subscribers_in_their_language(): void
    {
        Mail::fake();
        Subscriber::create(['email' => 'fr@example.com', 'lang' => 'fr']);
        Subscriber::create(['email' => 'en@example.com', 'lang' => 'en']);
        $post = $this->publishedPost();
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/admin/newsletter/'.$post->id)->assertOk()->assertJson(['sent' => 2, 'failed' => 0]);

        Mail::assertSent(PostNewsletterMail::class, 2);
        Mail::assertSent(PostNewsletterMail::class, fn ($m) => $m->hasTo('en@example.com') && $m->title === $post->title['en'] && str_contains($m->render(), 'Read the article'));
        $this->assertNotNull($post->fresh()->newsletter_sent_at);

        // Deuxième envoi : refusé sans confirmation explicite.
        $this->postJson('/api/admin/newsletter/'.$post->id)->assertUnprocessable();
        $this->postJson('/api/admin/newsletter/'.$post->id, ['force' => true])->assertOk();
    }

    public function test_one_click_unsubscribe_with_signed_link_only(): void
    {
        $sub = Subscriber::create(['email' => 'lecteur@example.com', 'lang' => 'fr']);
        $url = PostNewsletterMail::unsubscribeUrl($sub);
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        $this->get('/newsletter/desinscription/'.$sub->id)->assertForbidden();
        $this->get($path.'x')->assertForbidden();
        $this->get($path)->assertOk()->assertSee('Vous êtes désinscrit(e)');
        $this->assertSoftDeleted($sub);

        $other = Subscriber::create(['email' => 'autre@example.com', 'lang' => 'en']);
        $u = PostNewsletterMail::unsubscribeUrl($other);
        $this->post(parse_url($u, PHP_URL_PATH).'?'.parse_url($u, PHP_URL_QUERY))->assertNoContent();
        $this->assertSoftDeleted($other);
    }

    public function test_welcome_email_has_an_unsubscribe_link(): void
    {
        Mail::fake();
        $this->postJson('/api/subscribers', ['email' => 'nouveau@example.com', 'lang' => 'en'])->assertCreated();
        Mail::assertSent(WelcomeSubscriberMail::class, fn ($m) => str_contains((string) $m->unsubscribe, '/newsletter/desinscription/') && str_contains($m->render(), 'Unsubscribe in one click'));
    }

    public function test_generated_share_image_for_articles_without_cover(): void
    {
        $post = $this->publishedPost();

        $this->get('/blog/'.$post->slug.'/couverture-fr.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/blog/inconnu/couverture-fr.png')->assertNotFound();
        $this->get('/fr/blog/'.$post->slug)->assertSee('/blog/'.$post->slug.'/couverture-fr.png', false);
    }
}
