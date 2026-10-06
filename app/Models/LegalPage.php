<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;

/** Page légale (mentions légales, confidentialité, CGU, cookies…) en FR / EN. */
class LegalPage extends Model
{
    use ContentModel, Trashable;

    /** Champs saisis avec l'éditeur de texte enrichi (HTML nettoyé). */
    protected array $rich = ['body'];

    // « key » (page système) n'est pas modifiable depuis l'administration.
    protected $fillable = ['slug_fr', 'slug_en', 'title', 'body', 'published', 'position'];

    protected function casts(): array
    {
        return ['title' => 'array', 'body' => 'array', 'published' => 'boolean'];
    }

    protected function frontRelations(): array
    {
        return ['updatedAt' => $this->updated_at?->toDateString()];
    }

    /** Page publiée correspondant à un slug, dans l'une ou l'autre langue. */
    public static function findBySlug(string $slug): ?self
    {
        return static::published()->where(fn ($q) => $q->where('slug_fr', $slug)->orWhere('slug_en', $slug))->first();
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->title);
    }

    public function trashMeta(): string
    {
        return '/'.$this->slug_fr;
    }
}
