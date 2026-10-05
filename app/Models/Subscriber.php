<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use Trashable;

    protected $fillable = ['email', 'lang'];

    public function trashLabel(): string
    {
        return $this->email;
    }

    public function trashMeta(): string
    {
        return strtoupper($this->lang);
    }

    public function toFront(): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'lang' => $this->lang, 'date' => $this->created_at?->format('Y-m-d')];
    }
}
