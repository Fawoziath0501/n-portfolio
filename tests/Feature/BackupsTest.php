<?php

namespace Tests\Feature;

use App\Mail\BackupMail;
use App\Models\User;
use App\Support\Backups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackupsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Backups::list() as $b) {
            File::delete(Backups::dir().'/'.$b['name']);
        }
        parent::tearDown();
    }

    public function test_command_creates_an_archive_and_mails_it(): void
    {
        Mail::fake();

        $this->artisan('portfolio:backup', ['--mail' => true])->assertSuccessful();

        $this->assertCount(1, Backups::list());
        Mail::assertSent(BackupMail::class, fn (BackupMail $m) => $m->attached);
    }

    public function test_admin_lists_creates_and_downloads_backups_only(): void
    {
        $this->getJson('/api/admin/backups')->assertUnauthorized();
        $this->actingAs(User::factory()->create());

        $name = $this->postJson('/api/admin/backups')->assertOk()->json('0.name');
        $this->get('/api/admin/backups/'.$name)->assertOk();
        $this->get('/api/admin/backups/..%2F..%2F.env')->assertNotFound();
        $this->get('/api/admin/backups/database.sqlite')->assertNotFound();
    }
}
