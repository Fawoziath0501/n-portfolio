<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillGroup extends Model
{
    use ContentModel, Trashable;

    protected $fillable = ['code', 'label', 'icon', 'visible', 'position'];

    protected $with = ['skills'];

    protected function casts(): array
    {
        return ['label' => 'array', 'visible' => 'boolean'];
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('position');
    }

    protected function frontRelations(): array
    {
        return ['skills' => $this->skills->map->toFront()->all()];
    }

    protected function syncFrontRelations(array $data): void
    {
        if (isset($data['skills'])) {
            $this->syncMany($this->skills(), $data['skills'], fn ($s) => Skill::attributesFromFront($s));
        }
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->label);
    }

    public function trashMeta(): string
    {
        return $this->skills->count().' compétence(s)';
    }
}
