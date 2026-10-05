<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Language extends Model
{
    use ContentModel;

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'position', 'profile_id'];
    }

    protected $fillable = ['name', 'level', 'cefr', 'featured', 'position'];

    protected function casts(): array
    {
        return ['name' => 'array', 'level' => 'array', 'featured' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
