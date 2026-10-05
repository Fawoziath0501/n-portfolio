<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use ContentModel, Trashable;

    protected $fillable = ['title', 'excerpt', 'icon', 'date', 'read_min', 'url', 'published', 'position'];

    protected $with = ['tags'];

    protected function casts(): array
    {
        return ['title' => 'array', 'excerpt' => 'array', 'date' => 'date:Y-m-d', 'read_min' => 'integer', 'published' => 'boolean'];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withPivot('position')->orderByPivot('position');
    }

    protected function frontRelations(): array
    {
        return ['tags' => $this->tags->pluck('name')->all()];
    }

    protected function syncFrontRelations(array $data): void
    {
        if (isset($data['tags'])) {
            $ids = Tag::idsFor($data['tags']);
            $this->tags()->sync(collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
        }
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->title);
    }

    public function trashMeta(): string
    {
        return (string) $this->date?->format('Y-m-d');
    }
}
