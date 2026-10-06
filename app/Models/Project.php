<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use ContentModel, Trashable;

    /** Champs saisis avec l'éditeur de texte enrichi (HTML nettoyé). */
    protected array $rich = ['context', 'problem', 'results'];

    protected $fillable = ['slug', 'title', 'category', 'role', 'summary', 'context', 'problem', 'contribution', 'solution', 'results', 'year', 'link', 'repo', 'featured', 'published', 'position'];

    protected $with = ['technologies', 'images'];

    protected function casts(): array
    {
        $i18n = array_fill_keys(['title', 'category', 'role', 'summary', 'context', 'problem', 'contribution', 'solution', 'results'], 'array');

        return $i18n + ['featured' => 'boolean', 'published' => 'boolean'];
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)->withPivot('position')->orderByPivot('position');
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'project_media')->withPivot('position')->orderByPivot('position');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    protected function frontRelations(): array
    {
        return [
            'tech' => $this->technologies->pluck('name')->all(),
            'images' => $this->images->pluck('url')->all(),
        ];
    }

    protected function syncFrontRelations(array $data): void
    {
        if (isset($data['tech'])) {
            $ids = Technology::idsFor($data['tech']);
            $this->technologies()->sync(collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
        }
        if (isset($data['images'])) {
            // Les captures en corbeille, invisibles dans l'administration, restent liées jusqu'à leur restauration.
            $trashed = $this->images()->onlyTrashed()->pluck('media.id');
            $ids = collect($data['images'])->map(fn ($u) => Media::fromUrl($u)?->id)->filter()->concat($trashed)->unique()->values();
            $this->images()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
        }
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->title);
    }

    public function trashMeta(): string
    {
        return Portfolio::tx($this->category);
    }
}
