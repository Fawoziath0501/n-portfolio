<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use ContentModel;

    protected $fillable = ['name', 'company', 'role', 'quote', 'published', 'position'];

    protected function casts(): array
    {
        return ['role' => 'array', 'quote' => 'array', 'published' => 'boolean'];
    }
}
