<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ErrorDigestTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        @mkdir(storage_path('framework/testing/logs'), 0777, true);
        $this->file = storage_path('framework/testing/logs/test-digest.log');
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_digest_is_sent_only_when_there_are_recent_errors(): void
    {
        Event::fake([MessageSent::class]);
        config(['mail.default' => 'array']);

        file_put_contents($this->file, '['.now()->subDays(3)->format('Y-m-d H:i:s')."] local.ERROR: Ancienne erreur\n");
        $this->artisan('portfolio:error-digest', ['--path' => dirname($this->file)])->expectsOutputToContain('Aucune erreur')->assertSuccessful();
        Event::assertNotDispatched(MessageSent::class);

        file_put_contents($this->file, '['.now()->format('Y-m-d H:i:s')."] local.ERROR: Boom récent\n#0 trace\n", FILE_APPEND);
        $this->artisan('portfolio:error-digest', ['--path' => dirname($this->file)])->expectsOutputToContain('erreur(s) signalée(s)')->assertSuccessful();
        Event::assertDispatched(MessageSent::class, fn ($e) => str_contains($e->message->getTextBody(), 'Boom récent'));
    }
}
