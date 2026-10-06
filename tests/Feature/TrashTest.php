<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrashTest extends TestCase
{
    public function test_list_and_restore(): void
    {
        $project = Project::first();
        $project->delete();

        $this->admin()->getJson('/api/admin/trash')
            ->assertOk()
            ->assertJsonPath('days', 30)
            ->assertJsonPath('items.0.type', 'projects')
            ->assertJsonPath('items.0.id', $project->id);

        $this->postJson("/api/admin/trash/projects/{$project->id}/restore")->assertOk();

        $this->assertNotSoftDeleted($project);
        $this->assertSame(0, $this->getJson('/api/admin/data')->json('trashCount'));
    }

    public function test_only_trashed_items_can_be_restored_or_destroyed(): void
    {
        $project = Project::first();
        $this->admin();

        $this->postJson("/api/admin/trash/projects/{$project->id}/restore")->assertNotFound();
        $this->deleteJson("/api/admin/trash/projects/{$project->id}")->assertNotFound();
        $this->deleteJson("/api/admin/trash/inconnu/{$project->id}")->assertNotFound();
    }

    public function test_force_delete(): void
    {
        $project = Project::first();
        $project->delete();

        $this->admin()->deleteJson("/api/admin/trash/projects/{$project->id}")->assertNoContent();

        $this->assertModelMissing($project);
    }

    public function test_media_file_is_removed_from_disk_only_when_force_deleted(): void
    {
        Storage::fake('public');
        $this->admin();

        $id = $this->post('/api/admin/media', ['files' => [UploadedFile::fake()->image('photo.jpg', 40, 30)]], ['Accept' => 'application/json'])
            ->assertCreated()->json('0.id');
        $media = Media::findOrFail($id);
        Storage::disk('public')->assertExists($media->path);

        $this->deleteJson("/api/admin/media/{$id}")->assertOk();
        Storage::disk('public')->assertExists($media->path);

        $this->deleteJson('/api/admin/trash')->assertNoContent();
        Storage::disk('public')->assertMissing($media->path);
        $this->assertModelMissing($media);
    }

    public function test_prune_removes_items_older_than_retention(): void
    {
        $old = Message::create(['name' => 'A', 'email' => 'a@example.com', 'body' => 'x']);
        $recent = Message::create(['name' => 'B', 'email' => 'b@example.com', 'body' => 'x']);
        $old->delete();
        $recent->delete();
        Message::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(31)]);

        $this->artisan('model:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertSoftDeleted($recent);
    }
}
