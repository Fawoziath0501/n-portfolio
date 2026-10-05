<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['name', 'path', 'url', 'kind', 'width', 'height', 'size'];

    public function toFront(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'src' => $this->url,
            'kind' => $this->kind,
            'w' => $this->width,
            'h' => $this->height,
            'size' => $this->size,
            'date' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
