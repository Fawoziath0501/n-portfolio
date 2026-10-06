<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    private array $msg = ['type' => 'contact', 'name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Bonjour, un projet à discuter.'];

    private function enable(array $forms = ['contact' => true, 'service' => true, 'newsletter' => true, 'login' => true]): void
    {
        Captcha::save(['provider' => 'turnstile', 'siteKey' => 'site-key', 'secret' => 'top-secret', 'forms' => $forms]);
    }

    public function test_honeypot_silently_drops_bots(): void
    {
        $this->postJson('/api/messages', $this->msg + ['website' => 'http://spam.example'])->assertCreated();
        $this->postJson('/api/subscribers', ['email' => 'bot@example.com', 'website' => 'x'])->assertCreated();

        $this->assertSame(0, Message::where('email', 'ada@example.com')->count());
        $this->assertFalse(Subscriber::where('email', 'bot@example.com')->exists());
    }

    public function test_forms_work_without_captcha_when_disabled(): void
    {
        $this->postJson('/api/messages', $this->msg)->assertCreated();
        $this->assertTrue(Message::where('email', 'ada@example.com')->exists());
    }

    public function test_protected_form_requires_a_valid_token(): void
    {
        $this->enable();
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->postJson('/api/messages', $this->msg)->assertStatus(422)->assertJsonValidationErrors('captcha');
        $this->postJson('/api/messages', $this->msg + ['captcha' => 'bad'])->assertStatus(422)->assertJsonValidationErrors('captcha');
        $this->postJson('/api/messages', $this->msg + ['captcha' => 'good'])->assertCreated();

        $this->assertSame(1, Message::where('email', 'ada@example.com')->count());
        Http::assertSent(fn ($r) => $r['secret'] === 'top-secret' && $r['response'] === 'good');
    }

    public function test_unprotected_form_skips_captcha(): void
    {
        $this->enable(['contact' => true, 'service' => true, 'newsletter' => false, 'login' => false]);
        Http::fake();

        $this->postJson('/api/subscribers', ['email' => 'new@example.com'])->assertCreated();
        Http::assertNothingSent();
    }

    public function test_admin_login_is_protected_and_secret_never_exposed(): void
    {
        $user = User::factory()->create(['email' => 'cap@example.com', 'password' => bcrypt('secret-pass')]);
        $this->enable();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->postJson('/api/admin/login', ['email' => 'cap@example.com', 'password' => 'secret-pass'])->assertStatus(422);
        $this->assertGuest();

        $page = $this->get('/admin')->getContent();
        $this->assertStringContainsString('site-key', $page);
        $this->assertStringNotContainsString('top-secret', $page);

        $this->actingAs($user);
        $admin = $this->getJson('/api/admin/data')->assertOk()->json('captcha');
        $this->assertTrue($admin['hasSecret']);
        $this->assertArrayNotHasKey('secret', $admin);
        $site = $this->get('/fr')->getContent();
        $this->assertStringContainsString('site-key', $site);
        $this->assertStringNotContainsString('top-secret', $site);
    }

    public function test_admin_can_configure_captcha(): void
    {
        $this->actingAs(User::factory()->create());

        $this->putJson('/api/admin/captcha', ['provider' => 'hcaptcha', 'siteKey' => ''])->assertStatus(422);
        $this->putJson('/api/admin/captcha', ['provider' => 'hcaptcha', 'siteKey' => 'k', 'secret' => 's', 'forms' => ['contact' => true]])
            ->assertOk()->assertJson(['provider' => 'hcaptcha', 'hasSecret' => true, 'forms' => ['contact' => true, 'login' => false]]);
        // Clé secrète vide : l'ancienne est conservée.
        $this->putJson('/api/admin/captcha', ['provider' => 'hcaptcha', 'siteKey' => 'k2', 'forms' => ['contact' => true]])->assertOk()->assertJson(['hasSecret' => true]);
        $this->assertTrue(Captcha::protects('contact'));
        $this->assertFalse(Captcha::protects('login'));
    }
}
