<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use ContentModel;

    protected $fillable = ['title', 'excerpt', 'tags', 'date', 'read_min', 'url', 'published', 'position'];

    protected function casts(): array
    {
        return ['title' => 'array', 'excerpt' => 'array', 'tags' => 'array', 'date' => 'date:Y-m-d', 'read_min' => 'integer', 'published' => 'boolean'];
    }
}
