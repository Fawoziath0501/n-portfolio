<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;

class SkillGroup extends Model
{
    use ContentModel;

    protected $fillable = ['code', 'label', 'skills', 'visible', 'position'];

    protected function casts(): array
    {
        return ['label' => 'array', 'skills' => 'array', 'visible' => 'boolean'];
    }
}
