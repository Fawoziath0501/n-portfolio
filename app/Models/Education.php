<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use ContentModel;

    protected $table = 'education';

    protected $fillable = ['degree', 'school', 'period', 'published', 'position'];

    protected function casts(): array
    {
        return ['degree' => 'array', 'published' => 'boolean'];
    }
}
