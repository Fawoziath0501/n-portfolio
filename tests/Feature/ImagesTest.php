<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagesTest extends TestCase
{
    private function png(string $path, int $w = 1600, int $h = 1200): Media
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 230, 240, 255));
        ob_start();
        imagepng($img);
        Storage::disk('public')->put($path, ob_get_clean());

        return Media::create(['name' => basename($path), 'path' => $path, 'url' => '/storage/'.$path, 'kind' => 'image', 'width' => $w, 'height' => $h]);
    }

    public function test_uploaded_files_get_a_domain_independent_address(): void
    {
        Storage::fake('public');

        $src = $this->admin()->post('/api/admin/media', ['files' => [\Illuminate\Http\UploadedFile::fake()->image('photo.jpg', 40, 30)]], ['Accept' => 'application/json'])
            ->assertCreated()->json('0.src');

        $this->assertMatchesRegularExpression('#^/storage/media/[A-Za-z0-9]+\.jpg$#', $src);
    }

    public function test_post_cover_is_saved_and_shared(): void
    {
        Storage::fake('public');
        $media = $this->png('media/couverture.png', 1200, 630);
        $post = tap(Post::query()->first())->update(['published' => true]); // articles d'exemple livrés en brouillon

        $this->admin()->putJson("/api/admin/posts/{$post->id}", ['item' => ['cover' => $media->url, 'body' => ['fr' => '<p>Texte</p><img src="'.$media->url.'" alt="Schéma">', 'en' => '']]])
            ->assertOk()->assertJsonPath('cover', $media->url);

        $this->assertSame($media->id, $post->fresh()->cover_id);
        $this->assertStringContainsString('<img src="'.$media->url.'" alt="Schéma" />', $post->fresh()->body['fr']);
        $this->get('/fr/blog/'.$post->slug)->assertSee('<meta property="og:image" content="http://localhost'.$media->url.'">', false);
    }

    public function test_certificate_original_is_never_public(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $media = $this->png('media/certificat-original.png');
        $cert = Certification::create(['name' => ['fr' => 'Certif', 'en' => 'Cert'], 'issuer' => 'X', 'published' => true]);
        $cert->forceFill(['image_id' => $media->id])->save();

        $site = $this->getJson('/api/site')->json('certifications.0');
        $this->assertArrayNotHasKey('image', $site);
        $this->assertStringStartsWith('/certificats/'.$cert->id.'/apercu.jpg?v=', $site['preview']);
        $this->assertStringNotContainsString('certificat-original', json_encode($this->getJson('/api/site')->json()));
        $this->get('/fr/a-propos')->assertDontSee('certificat-original');

        $res = $this->get('/certificats/'.$cert->id.'/apercu.jpg')->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Robots-Tag', 'noindex, noimageindex');
        [$w] = getimagesizefromstring($res->getContent());
        $this->assertSame(1000, $w, 'aperçu réduit');

        // L'administration, elle, voit le fichier original.
        $this->admin()->getJson('/api/admin/data')->assertJsonPath('certifications.0.image', $media->url);
    }

    public function test_certificate_preview_is_unavailable_without_image_or_when_unpublished(): void
    {
        Storage::fake('public');
        $cert = Certification::create(['name' => ['fr' => 'Sans photo'], 'published' => true]);
        $this->get('/certificats/'.$cert->id.'/apercu.jpg')->assertNotFound();

        $cert->forceFill(['image_id' => $this->png('media/c.png')->id, 'published' => false])->save();
        $this->get('/certificats/'.$cert->id.'/apercu.jpg')->assertNotFound();
    }
}
