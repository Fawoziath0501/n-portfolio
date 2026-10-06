<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use ContentModel, Trashable;

    /** Champs saisis avec l'éditeur de texte enrichi (HTML nettoyé). */
    protected array $rich = ['description'];

    protected $fillable = ['icon', 'title', 'description', 'published', 'position'];

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array', 'published' => 'boolean'];
    }

    /** Demandes reçues pour ce service. */
    public function requests(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->title);
    }
}
