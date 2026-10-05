<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Sérialisation commune des contenus : clés camelCase côté front,
 * colonnes snake_case côté base, ordre manuel via « position ».
 */
trait ContentModel
{
    public function toFront(): array
    {
        $out = [];
        foreach ($this->attributesToArray() as $k => $v) {
            if (in_array($k, ['created_at', 'updated_at', 'position'], true)) {
                continue;
            }
            $out[Str::camel($k)] = $v;
        }

        return $out;
    }

    public function fillFromFront(array $data): static
    {
        $attrs = [];
        foreach ($data as $k => $v) {
            $col = Str::snake($k);
            if ($this->isFillable($col)) {
                $attrs[$col] = $v;
            }
        }

        return $this->fill($attrs);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
