<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = ['email', 'lang'];

    public function toFront(): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'lang' => $this->lang, 'date' => $this->created_at?->format('Y-m-d')];
    }
}
