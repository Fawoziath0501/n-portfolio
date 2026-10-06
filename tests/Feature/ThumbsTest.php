<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Support\Thumbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_images_get_lightweight_webp_versions_exposed_to_the_site(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $id = $this->post('/api/admin/media', ['files' => [UploadedFile::fake()->image('capture.png', 1600, 800)]])->assertCreated()->json('0.id');
        $media = Media::findOrFail($id);

        $this->assertSame([480, 960], array_keys($media->variants));
        foreach (Thumbs::paths($media) as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertSame(1600, $this->getJson('/api/site')->json('thumbs')[$media->url]['w']);

        // Suppression définitive : l'original et ses versions allégées disparaissent.
        $paths = [$media->path, ...Thumbs::paths($media)];
        $media->forceDelete();
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_small_images_are_left_untouched(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $id = $this->post('/api/admin/media', ['files' => [UploadedFile::fake()->image('icone.png', 300, 300)]])->assertCreated()->json('0.id');

        $this->assertNull(Media::findOrFail($id)->variants);
    }
}
