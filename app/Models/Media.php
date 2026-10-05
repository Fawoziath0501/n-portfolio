<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use Trashable;

    protected $table = 'media';

    protected $fillable = ['name', 'path', 'url', 'kind', 'width', 'height', 'size'];

    protected static function booted(): void
    {
        // Le fichier n'est effacé du disque qu'à la suppression définitive.
        static::forceDeleted(function (Media $media) {
            if ($media->path) {
                Storage::disk('public')->delete($media->path);
            }
        });
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_media');
    }

    /** Retrouve (ou référence) un fichier à partir de son adresse. */
    public static function fromUrl(?string $url): ?self
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if ($found = static::where('url', $url)->first()) {
            return $found;
        }
        if ($trashed = static::onlyTrashed()->where('url', $url)->first()) {
            $trashed->restore();

            return $trashed;
        }

        return static::create([
            'name' => basename(parse_url($url, PHP_URL_PATH) ?: '') ?: $url,
            'url' => $url,
            'kind' => preg_match('/\.pdf($|\?)/i', $url) ? 'doc' : 'image',
        ]);
    }

    public function isUsed(): bool
    {
        return $this->projects()->exists()
            || Profile::where('photo_id', $this->id)->orWhere('cv_id', $this->id)->exists();
    }

    public function trashLabel(): string
    {
        return $this->name;
    }

    public function trashMeta(): string
    {
        return $this->kind === 'doc' ? 'PDF' : 'Image';
    }

    public function toFront(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'src' => $this->url,
            'kind' => $this->kind,
            'w' => $this->width,
            'h' => $this->height,
            'size' => $this->size,
            'date' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
