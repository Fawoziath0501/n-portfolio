<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['type', 'key', 'path', 'ref', 'device', 'sid'];
}
