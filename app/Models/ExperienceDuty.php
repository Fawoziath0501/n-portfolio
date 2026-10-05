<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperienceDuty extends Model
{
    public $timestamps = false;

    protected $fillable = ['text', 'position'];

    protected function casts(): array
    {
        return ['text' => 'array'];
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
}
