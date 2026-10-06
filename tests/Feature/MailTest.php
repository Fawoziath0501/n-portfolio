<?php

namespace Tests\Feature;

use App\Mail\ReplyMail;
use App\Models\Message;
use App\Models\Setting;
use App\Support\MailSettings;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTest extends TestCase
{
    private function smtp(): array
    {
        return ['enabled' => true, 'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'moi@example.com', 'password' => 'secret-smtp', 'fromAddress' => 'moi@example.com', 'fromName' => 'Fawoziath'];
    }

    public function test_smtp_settings_are_saved_with_an_encrypted_password_never_sent_back(): void
    {
        $res = $this->admin()->putJson('/api/admin/mail', $this->smtp())->assertOk();

        $this->assertArrayNotHasKey('password', $res->json());
        $this->assertTrue($res->json('hasPassword'));
        $stored = Setting::get('mail')['password'];
        $this->assertNotSame('secret-smtp', $stored);
        $this->assertStringNotContainsString('secret-smtp', json_encode($this->getJson('/api/admin/data')->json()));

        // Mot de passe vide : l'ancien est conservé.
        $this->putJson('/api/admin/mail', ['password' => ''] + $this->smtp())->assertOk();
        $this->assertSame($stored, Setting::get('mail')['password']);

        MailSettings::apply();
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('secret-smtp', config('mail.mailers.smtp.password'));
    }

    public function test_enabled_settings_require_a_server(): void
    {
        $this->admin()->putJson('/api/admin/mail', ['enabled' => true, 'host' => ''])->assertUnprocessable()->assertJsonValidationErrors('host');
    }

    public function test_reply_by_email_is_sent_and_kept_in_history(): void
    {
        Mail::fake();
        $message = Message::create(['name' => 'Ada', 'email' => 'ada@example.com', 'body' => "Bonjour,\nun devis ?", 'lang' => 'fr']);
        $this->admin()->putJson('/api/admin/mail', $this->smtp());

        $this->postJson("/api/admin/messages/{$message->id}/reply", ['subject' => 'Re: votre demande', 'body' => 'Bonjour Ada, avec plaisir.'])
            ->assertOk()->assertJsonPath('status', 'replied')->assertJsonPath('replies.0.channel', 'email');

        Mail::assertSent(ReplyMail::class, fn ($m) => $m->hasTo('ada@example.com') && $m->replySubject === 'Re: votre demande');
        $this->assertStringContainsString('> un devis ?', (new ReplyMail($message, 'S', 'Corps'))->render());
    }

    public function test_reply_is_refused_when_sending_is_not_configured(): void
    {
        $message = Message::create(['name' => 'Ada', 'email' => 'ada@example.com', 'body' => 'x']);

        $this->admin()->postJson("/api/admin/messages/{$message->id}/reply", ['subject' => 'Re', 'body' => 'Salut'])
            ->assertUnprocessable()->assertJsonPath('errors.mail.0', fn ($m) => str_contains($m, 'pas configuré'));
        $this->assertSame(0, $message->replies()->count());
    }

    public function test_whatsapp_reply_is_recorded(): void
    {
        $message = Message::create(['name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '+229 01 00 00 00', 'body' => 'x']);

        $this->admin()->postJson("/api/admin/messages/{$message->id}/whatsapp", ['body' => 'Bonjour Ada'])
            ->assertOk()->assertJsonPath('replies.0.channel', 'whatsapp')->assertJsonPath('status', 'replied');
    }

    public function test_test_email(): void
    {
        Mail::fake();
        $this->admin()->putJson('/api/admin/mail', $this->smtp());

        $this->postJson('/api/admin/mail/test', ['to' => 'moi@example.com'])->assertOk();
    }
}
