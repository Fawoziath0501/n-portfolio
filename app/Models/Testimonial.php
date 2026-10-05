<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use ContentModel, Trashable;

    protected $fillable = ['project_id', 'name', 'company', 'role', 'quote', 'published', 'position'];

    protected function casts(): array
    {
        return ['role' => 'array', 'quote' => 'array', 'published' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function trashLabel(): string
    {
        return $this->name ?: '(sans nom)';
    }

    public function trashMeta(): string
    {
        return $this->company;
    }
}
