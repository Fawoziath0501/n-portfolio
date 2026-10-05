<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Texte de l'interface publique, en français et en anglais. */
class UiLabel extends Model
{
    protected $fillable = ['key', 'group', 'fr', 'en', 'position'];

    /** Dictionnaire { clé: { fr, en } } envoyé au site public. */
    public static function dictionary(): array
    {
        return static::orderBy('position')->get(['key', 'fr', 'en'])
            ->mapWithKeys(fn ($l) => [$l->key => ['fr' => $l->fr, 'en' => $l->en]])->all();
    }

    /** Liste détaillée pour l'écran « Textes du site » de l'administration. */
    public static function forAdmin(): array
    {
        return static::orderBy('position')->get(['id', 'key', 'group', 'fr', 'en'])->toArray();
    }
}
