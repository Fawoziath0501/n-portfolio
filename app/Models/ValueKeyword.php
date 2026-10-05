<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValueKeyword extends Model
{
    public $timestamps = false;

    protected $fillable = ['label', 'position'];

    protected function casts(): array
    {
        return ['label' => 'array'];
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(ProfileValue::class, 'profile_value_id');
    }
}
