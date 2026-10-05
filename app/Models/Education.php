<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use ContentModel, Trashable;

    protected $table = 'education';

    protected $fillable = ['degree', 'school', 'period', 'published', 'position'];

    protected function casts(): array
    {
        return ['degree' => 'array', 'published' => 'boolean'];
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->degree);
    }

    public function trashMeta(): string
    {
        return $this->school;
    }
}
