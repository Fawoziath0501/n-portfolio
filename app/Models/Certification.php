<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use ContentModel, Trashable;

    protected $fillable = ['name', 'issuer', 'date', 'verify', 'published', 'position'];

    protected function casts(): array
    {
        return ['name' => 'array', 'published' => 'boolean'];
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->name);
    }

    public function trashMeta(): string
    {
        return $this->issuer;
    }
}
