<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use ContentModel;

    protected $fillable = ['name', 'issuer', 'date', 'verify', 'published', 'position'];

    protected function casts(): array
    {
        return ['name' => 'array', 'published' => 'boolean'];
    }
}
