<?php

namespace App\Models\Concerns;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            if (in_array($col, $this->getFillable(), true)) {
                // Champs saisis avec l'éditeur enrichi ($rich) : HTML nettoyé avant l'enregistrement.
                $attrs[$col] = in_array($col, $this->rich ?? [], true) && $v !== null ? RichText::cleanI18n($v) : ($v ?? $this->emptyValue($col));
            }
        }

        return $this->fill($attrs);
    }

    /**
     * Valeur d'un champ vidé dans l'administration (Laravel transforme « » en null) :
     * null si la colonne l'accepte, sinon [] pour un champ JSON et « » pour un texte.
     */
    private function emptyValue(string $col): mixed
    {
        static $nullable = [];
        $nullable[$this->getTable()] ??= collect(Schema::getColumns($this->getTable()))->pluck('nullable', 'name')->all();

        if ($nullable[$this->getTable()][$col] ?? true) {
            return null;
        }

        return $this->hasCast($col, ['array', 'json']) ? [] : '';
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

    /**
     * Fichier de la médiathèque lié par une relation belongsTo (couverture, photo…), à partir de son adresse.
     * Un fichier en corbeille, invisible dans l'administration, garde son lien jusqu'à sa restauration.
     */
    protected function linkMedia(string $relation, string $key, array $data): void
    {
        if (! array_key_exists($key, $data)) {
            return;
        }
        $fk = $this->{$relation}()->getForeignKeyName();
        $trashed = $this->{$fk} ? \App\Models\Media::onlyTrashed()->find($this->{$fk}) : null;
        $this->{$relation}()->associate(\App\Models\Media::fromUrl($data[$key]) ?? $trashed);
        if ($this->isDirty($fk)) {
            $this->save();
        }
    }

    /**
     * Texte traduisible « un point par ligne » → lignes appariées FR / EN, ligne à ligne.
     * Une ligne vide dans une langue garde l'alignement (traduction manquante) ; aucune ligne n'est perdue.
     */
    protected static function pairLines(?array $value): array
    {
        $split = fn ($s) => array_map('trim', explode("\n", trim((string) $s)));
        $fr = $split($value['fr'] ?? '');
        $en = $split($value['en'] ?? '');
        $rows = array_map(fn ($i) => ['fr' => $fr[$i] ?? '', 'en' => $en[$i] ?? ''], range(0, max(count($fr), count($en)) - 1));

        return array_values(array_filter($rows, fn ($r) => $r['fr'] !== '' || $r['en'] !== ''));
    }

    /** Inverse de pairLines() : les lignes vides restent à leur place pour conserver l'alignement. */
    protected static function joinLines($rows): array
    {
        return [
            'fr' => rtrim(collect($rows)->pluck('fr')->implode("\n")),
            'en' => rtrim(collect($rows)->pluck('en')->implode("\n")),
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
