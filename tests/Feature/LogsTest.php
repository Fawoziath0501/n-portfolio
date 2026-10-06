<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = storage_path('logs/test-admin.log');
        $now = now()->format('Y-m-d H:i:s');
        file_put_contents($this->file, "[2020-01-01 10:00:00] local.INFO: Ancienne info\n"
            ."[$now] local.ERROR: Boom dans le contrôleur {\"exception\":\"x\"}\n#0 app/Http/Foo.php(12): bar()\n#1 {main}\n"
            ."[$now] local.WARNING: Captcha : vérification impossible\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_guests_cannot_read_logs(): void
    {
        $this->getJson('/api/admin/logs')->assertUnauthorized();
        $this->getJson('/api/admin/logs/test-admin.log')->assertUnauthorized();
    }

    public function test_admin_reads_filters_downloads_and_clears_logs(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/admin/logs')->assertOk()->assertJsonFragment(['name' => 'test-admin.log']);

        $res = $this->getJson('/api/admin/logs/test-admin.log')->assertOk();
        $this->assertSame(['info' => 1, 'error' => 1, 'warning' => 1], $res->json('counts'));
        $this->assertSame('warning', $res->json('entries.0.level')); // plus récent d'abord
        $this->assertStringContainsString('#0 app/Http/Foo.php', $res->json('entries.1.details'));

        $this->getJson('/api/admin/logs/test-admin.log?level=error')->assertOk()->assertJsonCount(1, 'entries');
        $this->getJson('/api/admin/logs/test-admin.log?q=captcha')->assertOk()->assertJsonPath('entries.0.level', 'warning');
        $this->assertGreaterThanOrEqual(1, $this->getJson('/api/admin/data')->json('logErrors'));

        $this->get('/api/admin/logs/test-admin.log/download')->assertOk();
        $this->deleteJson('/api/admin/logs/test-admin.log')->assertOk();
        $this->assertSame('', file_get_contents($this->file));
    }

    public function test_only_log_files_are_reachable(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/admin/logs/..%2F..%2F.env')->assertNotFound();
        $this->getJson('/api/admin/logs/.env')->assertNotFound();
        $this->getJson('/api/admin/logs/absent.log')->assertNotFound();
    }
}
