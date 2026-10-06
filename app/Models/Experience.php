<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experience extends Model
{
    use ContentModel, Trashable;

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'position', 'company_id'];
    }

    /** Champs saisis avec l'éditeur de texte enrichi (HTML nettoyé). */
    protected array $rich = ['description'];

    protected $fillable = ['role', 'start', 'end', 'description', 'current', 'published', 'position'];

    protected $with = ['company', 'duties'];

    protected function casts(): array
    {
        return ['role' => 'array', 'start' => 'array', 'end' => 'array', 'description' => 'array', 'current' => 'boolean', 'published' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function duties(): HasMany
    {
        return $this->hasMany(ExperienceDuty::class)->orderBy('position');
    }

    /** Côté front, l'entreprise et son lieu sont à plat ; les missions sont « une par ligne ». */
    protected function frontRelations(): array
    {
        return [
            'company' => $this->company?->name ?? '',
            'location' => $this->company?->location ?? ['fr' => '', 'en' => ''],
            'duties' => self::joinLines($this->duties->pluck('text')),
        ];
    }

    protected function syncFrontRelations(array $data): void
    {
        if (array_key_exists('company', $data)) {
            $name = trim((string) $data['company']);
            $company = $name === '' ? null : Company::firstOrCreate(['name' => $name]);
            if ($company && isset($data['location'])) {
                $company->update(['location' => $data['location']]);
            }
            $this->company()->associate($company)->save();
        }
        if (isset($data['duties'])) {
            $this->duties()->delete();
            foreach (self::pairLines($data['duties']) as $i => $text) {
                $this->duties()->create(['text' => $text, 'position' => $i]);
            }
        }
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->role);
    }

    public function trashMeta(): string
    {
        return (string) $this->company?->name;
    }
}
