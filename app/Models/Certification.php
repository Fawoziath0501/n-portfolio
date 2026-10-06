<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use ContentModel, Trashable;

    protected $fillable = ['name', 'issuer', 'date', 'verify', 'published', 'position'];

    protected $with = ['image'];

    protected function casts(): array
    {
        return ['name' => 'array', 'published' => 'boolean'];
    }

    /** Photo du certificat (médiathèque) : jamais servie telle quelle sur le site public. */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'position', 'image_id'];
    }

    /** Administration : adresse du fichier original ; site public : seulement l'aperçu filigrané (toPublic). */
    protected function frontRelations(): array
    {
        return ['image' => $this->image?->url ?? '', 'preview' => $this->previewUrl()];
    }

    public function toPublic(): array
    {
        return array_diff_key($this->toFront(), ['image' => true]);
    }

    /** Aperçu réduit et filigrané, versionné pour être recalculé quand la photo change. */
    public function previewUrl(): string
    {
        return $this->image && $this->image->path
            ? '/certificats/'.$this->id.'/apercu.jpg?v='.substr(md5($this->image->path.$this->image->updated_at), 0, 8)
            : '';
    }

    protected function syncFrontRelations(array $data): void
    {
        $this->linkMedia('image', 'image', $data);
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->name);
    }

    public function trashMeta(): string
    {
        return $this->issuer;
    }
}
