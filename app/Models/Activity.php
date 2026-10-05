<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['msg'];

    public static function log(string $msg): void
    {
        static::create(['msg' => $msg]);
        static::whereNotIn('id', static::latest('id')->limit(30)->pluck('id'))->delete();
    }
}
