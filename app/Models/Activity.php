<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Journal des modifications faites dans l'administration (30 dernières). */
class Activity extends Model
{
    protected $fillable = ['user_id', 'msg'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(?string $msg): void
    {
        if (! $msg) {
            return;
        }
        static::create(['user_id' => auth()->id(), 'msg' => Str::limit($msg, 250)]);
        static::whereNotIn('id', static::latest('id')->limit(30)->pluck('id'))->delete();
    }
}
