<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'location', 'website'];

    protected function casts(): array
    {
        return ['location' => 'array'];
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }
}
