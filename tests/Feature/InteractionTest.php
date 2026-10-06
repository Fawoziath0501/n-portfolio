<?php

namespace Tests\Feature;

use App\Mail\NewMessageMail;
use App\Models\Event;
use App\Models\Message;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    public function test_contact_message_is_stored_and_notified(): void
    {
        Mail::fake();

        $this->postJson('/api/messages', [
            'name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '+229 01 97 36 32 97', 'subject' => 'Bonjour',
            'message' => 'Un message suffisamment long.', 'lang' => 'en',
        ])->assertCreated();

        $message = Message::sole();
        $this->assertSame(['contact', 'Bonjour', 'en'], [$message->type, $message->subject, $message->lang]);
        Mail::assertSent(NewMessageMail::class);
    }

    public function test_service_request_is_linked_to_its_service(): void
    {
        Mail::fake();
        $service = Service::published()->firstOrFail();

        $this->postJson('/api/messages', [
            'type' => 'service', 'name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '+229 01 00 00 00 00',
            'service' => $service->title['en'], 'when' => '1 mois', 'message' => 'Besoin de refaire mon site vitrine.',
        ])->assertCreated();

        $message = Message::sole();
        $this->assertSame($service->id, $message->service_id);
        $this->assertStringContainsString('+229 01 00 00 00 00', $message->body);
        $this->assertStringContainsString('1 mois', $message->body);
    }

    public function test_message_validation(): void
    {
        $this->postJson('/api/messages', ['name' => '', 'email' => 'x', 'message' => 'court'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'message']);
    }

    public function test_phone_is_required_and_checked(): void
    {
        $base = ['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Un message assez long.'];

        $this->postJson('/api/messages', $base)->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/api/messages', $base + ['phone' => '12'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/api/messages', $base + ['phone' => 'abc'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/api/messages', $base + ['phone' => '+229 01 97 36 32 97'])->assertCreated();
        $this->assertSame('+229 01 97 36 32 97', Message::sole()->phone);
    }

    public function test_no_notification_when_disabled(): void
    {
        Mail::fake();
        Setting::put('settings', ['notifyOnMessage' => false] + Setting::get('settings'));

        $this->postJson('/api/messages', ['name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '0197363297', 'message' => 'Un message assez long.'])->assertCreated();

        Mail::assertNotSent(NewMessageMail::class); // l'accusé de réception au visiteur part quand même
    }

    public function test_subscribe_rejects_duplicates_and_restores_trashed(): void
    {
        $this->postJson('/api/subscribers', ['email' => 'Ada@Example.com'])->assertCreated();
        $this->postJson('/api/subscribers', ['email' => 'ada@example.com'])->assertStatus(409);

        Subscriber::sole()->delete();
        $this->postJson('/api/subscribers', ['email' => 'ada@example.com', 'lang' => 'en'])->assertCreated();

        $this->assertSame(1, Subscriber::withTrashed()->count());
        $this->assertSame('en', Subscriber::sole()->lang);
    }

    public function test_tracking_keeps_only_external_referrers(): void
    {
        $this->postJson('/api/track', ['t' => 'pv', 'path' => '/fr', 'ref' => 'https://www.Google.com/search', 'sid' => 'a'])->assertNoContent();
        $this->postJson('/api/track', ['t' => 'pv', 'path' => '/en', 'ref' => 'http://localhost/fr', 'sid' => 'a'])->assertNoContent();

        $this->assertSame(['www.google.com', null], Event::orderBy('id')->pluck('ref')->all());
    }

    public function test_tracking_is_ignored_when_disabled(): void
    {
        $settings = Setting::get('settings');
        $settings['analytics']['provider'] = 'none';
        Setting::put('settings', $settings);

        $this->postJson('/api/track', ['t' => 'pv', 'sid' => 'a'])->assertNoContent();

        $this->assertSame(0, Event::count());
    }
}
