<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    protected $fillable = ['name'];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('position');
    }

    /** Ids des technologies (créées si besoin), dans l'ordre reçu. */
    public static function idsFor(array $names): array
    {
        return collect($names)->map(fn ($n) => trim((string) $n))->filter()->unique()->values()
            ->map(fn ($n) => static::firstOrCreate(['name' => $n])->id)->all();
    }
}
