<?php

namespace Tests\Feature;

use App\Mail\AckMail;
use App\Mail\NewMessageMail;
use App\Mail\WelcomeSubscriberMail;
use App\Models\Message;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VisitorEmailsTest extends TestCase
{
    use RefreshDatabase;

    private array $msg = ['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'phone' => '+229 01 97 36 32 97', 'subject' => 'Projet', 'message' => 'Texte du visiteur à ne jamais recopier.'];

    public function test_visitor_receives_an_acknowledgement_in_their_language(): void
    {
        Mail::fake();
        $service = Service::published()->firstOrFail();

        $this->postJson('/api/messages', $this->msg + ['type' => 'service', 'service' => $service->title['en'], 'lang' => 'en'])->assertCreated();

        Mail::assertSent(NewMessageMail::class);
        Mail::assertSent(AckMail::class, function (AckMail $mail) use ($service) {
            $html = $mail->render();

            return $mail->hasTo('ada@example.com')
                && $mail->lang === 'en'
                && str_contains($html, 'DEM-'.str_pad((string) Message::sole()->id, 4, '0', STR_PAD_LEFT))
                && str_contains($html, 'Hello Ada Lovelace')
                && str_contains($html, e($service->title['en']))
                && ! str_contains($html, 'ne jamais recopier');
        });
    }

    public function test_acknowledgement_can_be_disabled_and_ignores_suspicious_names(): void
    {
        Mail::fake();
        Setting::put('settings', ['autoReply' => false] + Setting::get('settings'));
        $this->postJson('/api/messages', $this->msg)->assertCreated();
        Mail::assertNotSent(AckMail::class);

        Setting::put('settings', ['autoReply' => true] + Setting::get('settings'));
        $this->postJson('/api/messages', ['name' => 'Gagnez vite www.spam.xyz'] + $this->msg)->assertCreated();
        Mail::assertSent(AckMail::class, fn (AckMail $mail) => $mail->greetName === null && str_contains($mail->render(), 'Bonjour,') && ! str_contains($mail->render(), 'spam.xyz'));
    }

    public function test_new_subscriber_receives_a_welcome_email(): void
    {
        Mail::fake();

        $this->postJson('/api/subscribers', ['email' => 'lecteur@example.com', 'lang' => 'fr'])->assertCreated();

        Mail::assertSent(WelcomeSubscriberMail::class, fn ($mail) => $mail->hasTo('lecteur@example.com') && str_contains($mail->render(), 'Merci pour votre inscription'));
    }
}
