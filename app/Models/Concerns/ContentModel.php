<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pont entre les modèles et les applications Vue :
 * clés camelCase côté front, colonnes snake_case côté base,
 * relations exposées via frontRelations() et enregistrées via syncFrontRelations().
 */
trait ContentModel
{
    /** Colonnes jamais envoyées au front. */
    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'position'];
    }

    public function toFront(): array
    {
        $out = [];
        foreach ($this->attributesToArray() as $k => $v) {
            if (! in_array($k, $this->frontHidden(), true)) {
                $out[Str::camel($k)] = $v;
            }
        }

        return method_exists($this, 'frontRelations') ? array_merge($out, $this->frontRelations()) : $out;
    }

    public function fillFromFront(array $data): static
    {
        $attrs = [];
        foreach ($data as $k => $v) {
            $col = Str::snake($k);
            if (in_array($col, $this->getFillable(), true) && ! is_null($v)) {
                $attrs[$col] = $v;
            }
        }

        return $this->fill($attrs);
    }

    /** Enregistre l'élément et ses relations en une seule transaction. */
    public function saveFromFront(array $data): static
    {
        DB::transaction(function () use ($data) {
            $this->fillFromFront($data)->save();
            if (method_exists($this, 'syncFrontRelations')) {
                $this->syncFrontRelations($data);
            }
        });

        return $this;
    }

    /**
     * Synchronise une relation hasMany avec une liste venue du front :
     * mise à jour par id, création des nouveaux, suppression des absents, ordre conservé.
     * Retourne les enfants dans l'ordre reçu (utile pour synchroniser un niveau de plus).
     */
    protected function syncMany(HasMany $relation, array $items, callable $map): array
    {
        $children = [];
        foreach (array_values($items) as $i => $item) {
            $attrs = $map($item);
            if (in_array('position', $relation->getRelated()->getFillable(), true)) {
                $attrs['position'] = $i;
            }
            $existing = isset($item['id']) && is_numeric($item['id']) ? $relation->getRelated()->newQuery()
                ->where($relation->getForeignKeyName(), $this->getKey())->find($item['id']) : null;
            $children[] = $existing ? tap($existing)->update($attrs) : $relation->create($attrs);
        }
        $relation->getRelated()->newQuery()
            ->where($relation->getForeignKeyName(), $this->getKey())
            ->whereNotIn($relation->getRelated()->getKeyName(), array_map(fn ($c) => $c->getKey(), $children))
            ->delete();

        return $children;
    }

    /** Texte traduisible « un point par ligne » → lignes appariées FR / EN. */
    protected static function pairLines(?array $value): array
    {
        $split = fn ($s) => array_values(array_filter(array_map('trim', explode("\n", (string) $s)), 'strlen'));
        $fr = $split($value['fr'] ?? '');
        $en = $split($value['en'] ?? '');

        return array_map(fn ($i) => ['fr' => $fr[$i] ?? '', 'en' => $en[$i] ?? ''], array_keys($fr ?: $en));
    }

    /** Inverse de pairLines(). */
    protected static function joinLines($rows): array
    {
        return [
            'fr' => collect($rows)->pluck('fr')->filter()->implode("\n"),
            'en' => collect($rows)->pluck('en')->filter()->implode("\n"),
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy($this->qualifyColumn('position'))->orderBy($this->qualifyColumn('id'));
    }

    public function scopePublished($query)
    {
        return $query->where($this->qualifyColumn('published'), true);
    }
}
