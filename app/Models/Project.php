<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use ContentModel;

    protected $fillable = ['slug', 'title', 'category', 'role', 'summary', 'context', 'problem', 'contribution', 'solution', 'results', 'year', 'link', 'repo', 'images', 'tech', 'featured', 'published', 'position'];

    protected function casts(): array
    {
        return ['title' => 'array', 'category' => 'array', 'role' => 'array', 'summary' => 'array', 'context' => 'array', 'problem' => 'array', 'contribution' => 'array', 'solution' => 'array', 'results' => 'array', 'images' => 'array', 'tech' => 'array', 'featured' => 'boolean', 'published' => 'boolean'];
    }
}
