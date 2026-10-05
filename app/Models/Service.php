<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use ContentModel;

    protected $fillable = ['icon', 'title', 'description', 'published', 'position'];

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array', 'published' => 'boolean'];
    }
}
