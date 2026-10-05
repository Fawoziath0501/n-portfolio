<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sections de la page d'accueil : affichage et ordre. */
class HomeSection extends Model
{
    protected $fillable = ['type', 'enabled', 'position'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function toFront(): array
    {
        return ['id' => $this->id, 'type' => $this->type, 'enabled' => $this->enabled];
    }
}
