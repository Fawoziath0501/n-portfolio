<?php

namespace Tests\Feature;

use App\Mail\NewMessageMail;
use App\Models\Experience;
use App\Models\Media;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Défauts relevés lors de la relecture du code. */
class ReviewFixesTest extends TestCase
{
    public function test_a_cleared_field_stays_empty(): void
    {
        $project = Project::where('slug', 'presentia')->first();
        $project->update(['link' => 'https://exemple.com', 'year' => '2024']);
        $this->admin();

        $this->putJson("/api/admin/projects/{$project->id}", ['item' => ['link' => '', 'year' => '']])->assertOk();
        $this->assertSame(['', ''], [$project->fresh()->link, $project->fresh()->year]);

        $profile = $this->getJson('/api/admin/data')->json('profile');
        $profile['phone'] = '';
        $profile['since'] = '';
        $this->putJson('/api/admin/documents/profile', ['value' => $profile])->assertOk();
        $this->assertSame('', Profile::current()->phone);
        $this->assertNull(Profile::current()->since);
    }

    public function test_an_empty_slug_is_rejected(): void
    {
        $project = Project::first();

        $this->admin()->putJson("/api/admin/projects/{$project->id}", ['item' => ['slug' => '']])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_backup_import_keeps_current_projects_restorable(): void
    {
        $this->admin();
        $backup = $this->getJson('/api/admin/export')->json();
        $before = Project::count();

        $this->postJson('/api/admin/import', ['data' => $backup])->assertNoContent();

        $this->assertSame($before, Project::count());
        $this->assertSame($before, Project::onlyTrashed()->count());
        $this->assertStringEndsWith('-ancien', Project::onlyTrashed()->first()->slug);
    }

    public function test_trashed_media_keeps_its_links_without_being_restored(): void
    {
        $media = Media::fromUrl('https://cdn.exemple.com/portrait.jpg');
        $project = Project::first();
        $project->images()->sync([$media->id => ['position' => 0]]);
        Profile::current()->photo()->associate($media)->save();
        $media->delete();
        $this->admin();

        // L'administration ne voit plus le fichier : elle renvoie un profil sans photo et un projet sans capture.
        $profile = $this->getJson('/api/admin/data')->json('profile');
        $this->assertSame('', $profile['photo']);
        $this->putJson('/api/admin/documents/profile', ['value' => $profile])->assertOk();
        $this->putJson("/api/admin/projects/{$project->id}", ['item' => ['images' => []]])->assertOk();

        $this->assertSoftDeleted($media);
        $media->restore();
        $this->assertSame($media->url, Profile::current()->toFront()['photo']);
        $this->assertSame([$media->url], $project->fresh()->toFront()['images']);
    }

    public function test_partial_urls_do_not_create_media(): void
    {
        $count = Media::count();

        $this->assertNull(Media::fromUrl('https:/'));
        $this->assertNull(Media::fromUrl('h'));
        $this->assertNotNull(Media::fromUrl('/storage/media/cv.pdf'));
        $this->assertSame($count + 1, Media::count());
    }

    public function test_svg_upload_is_refused(): void
    {
        Storage::fake('public');

        $this->admin()->post('/api/admin/media', ['files' => [UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml')]], ['Accept' => 'application/json'])
            ->assertUnprocessable();
    }

    public function test_duty_lines_keep_their_alignment(): void
    {
        $exp = Experience::first();

        $this->admin()->putJson("/api/admin/experiences/{$exp->id}", ['item' => ['duties' => ['fr' => "Un\n\nTrois", 'en' => "One\nTwo\nThree\nFour"]]])->assertOk();

        $this->assertSame(['fr' => "Un\n\nTrois", 'en' => "One\nTwo\nThree\nFour"], $exp->fresh()->toFront()['duties']);
    }

    public function test_maintenance_hides_content_from_the_server(): void
    {
        Setting::put('settings', ['maintenance' => true] + Setting::get('settings'));

        $this->get('/fr')->assertStatus(503)->assertSee('noindex', false);
        $data = $this->getJson('/api/site')->json();
        $this->assertTrue($data['settings']['maintenance']);
        $this->assertSame([], $data['projects']);
    }

    public function test_notification_mail_is_not_html_escaped(): void
    {
        $message = Message::create(['name' => "O'Neil", 'email' => 'o@example.com', 'body' => "J'aimerais un devis."]);

        $text = (new NewMessageMail($message))->render();

        $this->assertStringContainsString("J'aimerais un devis.", $text);
        $this->assertStringContainsString("O'Neil", $text);
    }
}
