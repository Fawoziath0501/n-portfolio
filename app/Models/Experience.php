<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use ContentModel;

    protected $fillable = ['company', 'location', 'role', 'start', 'end', 'description', 'duties', 'current', 'published', 'position'];

    protected function casts(): array
    {
        return ['location' => 'array', 'role' => 'array', 'start' => 'array', 'end' => 'array', 'description' => 'array', 'duties' => 'array', 'current' => 'boolean', 'published' => 'boolean'];
    }
}
