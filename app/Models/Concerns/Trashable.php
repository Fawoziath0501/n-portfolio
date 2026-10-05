<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Corbeille : suppression logique (deleted_at), restauration,
 * et purge définitive automatique après Portfolio::TRASH_DAYS jours (php artisan model:prune).
 */
trait Trashable
{
    use Prunable, SoftDeletes;

    public function prunable()
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(\App\Support\Portfolio::TRASH_DAYS));
    }

    /** Libellé affiché dans la corbeille de l'administration. */
    abstract public function trashLabel(): string;

    public function trashMeta(): string
    {
        return '';
    }
}
