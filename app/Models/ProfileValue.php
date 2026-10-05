<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Étape de la méthode de travail (Comprendre, Construire, Améliorer). */
class ProfileValue extends Model
{
    use ContentModel;

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'position', 'profile_id'];
    }

    protected $fillable = ['icon', 'title', 'text', 'position'];

    protected function casts(): array
    {
        return ['title' => 'array', 'text' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(ValueKeyword::class)->orderBy('position');
    }

    protected function frontRelations(): array
    {
        return ['keys' => $this->keywords->pluck('label')->all()];
    }
}
