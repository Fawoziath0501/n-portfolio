<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Profil de la propriétaire du portfolio (une seule ligne). */
class Profile extends Model
{
    use ContentModel;

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'photo_id', 'cv_id', 'cta_primary', 'cta_secondary'];
    }

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'title', 'stack', 'tagline', 'bio', 'location', 'email', 'phone', 'hours', 'since',
        'availability', 'availability_short', 'status', 'reply_time', 'reply_delay', 'formats', 'zone', 'soft_skills', 'cta_primary', 'cta_secondary',
    ];

    protected function casts(): array
    {
        $i18n = array_fill_keys(['title', 'stack', 'tagline', 'bio', 'location', 'hours', 'availability', 'availability_short', 'status', 'reply_time', 'reply_delay', 'formats', 'zone', 'soft_skills', 'cta_primary', 'cta_secondary'], 'array');

        return $i18n + ['since' => 'integer'];
    }

    public static function current(): self
    {
        return static::with(['photo', 'cv', 'languages', 'values.keywords', 'socials'])->firstOrFail();
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cv_id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(Language::class)->orderBy('position');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProfileValue::class)->orderBy('position');
    }

    public function socials(): HasMany
    {
        return $this->hasMany(SocialLink::class)->orderBy('position');
    }

    protected function frontRelations(): array
    {
        return [
            'since' => (string) ($this->since ?? ''),
            'photo' => $this->photo?->url ?? '',
            'cv' => $this->cv?->url ?? '',
            'languages' => $this->languages->map->toFront()->all(),
            'values' => $this->values->map->toFront()->all(),
            'socials' => $this->socials->map->toFront()->all(),
        ];
    }

    /** Fichier lié mais en corbeille : invisible dans l'administration, son lien est conservé jusqu'à sa restauration. */
    private function trashedMedia(string $column): ?Media
    {
        return $this->{$column} ? Media::onlyTrashed()->find($this->{$column}) : null;
    }

    protected function syncFrontRelations(array $data): void
    {
        if (array_key_exists('photo', $data)) {
            $this->photo()->associate(Media::fromUrl($data['photo']) ?? $this->trashedMedia('photo_id'));
        }
        if (array_key_exists('cv', $data)) {
            $this->cv()->associate(Media::fromUrl($data['cv']) ?? $this->trashedMedia('cv_id'));
        }
        $this->save();

        if (isset($data['languages'])) {
            $this->syncMany($this->languages(), $data['languages'], fn ($l) => [
                'name' => $l['name'] ?? [], 'level' => $l['level'] ?? [], 'cefr' => $l['cefr'] ?? null, 'featured' => (bool) ($l['featured'] ?? false),
            ]);
        }
        if (isset($data['values'])) {
            $values = $this->syncMany($this->values(), $data['values'], fn ($v) => [
                'icon' => $v['icon'] ?? 'star', 'title' => $v['title'] ?? [], 'text' => $v['text'] ?? [],
            ]);
            foreach ($values as $i => $value) {
                $value->keywords()->delete();
                foreach (array_values($data['values'][$i]['keys'] ?? []) as $k => $label) {
                    $value->keywords()->create(['label' => $label, 'position' => $k]);
                }
            }
        }
        if (isset($data['socials'])) {
            $this->syncMany($this->socials(), $data['socials'], fn ($s) => [
                'label' => $s['label'] ?? '', 'icon' => $s['icon'] ?? 'link', 'url' => $s['url'] ?? '', 'handle' => $s['handle'] ?? '', 'visible' => (bool) ($s['visible'] ?? true),
            ]);
        }
    }
}
