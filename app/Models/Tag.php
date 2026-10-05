<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['slug', 'name'];

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withPivot('position');
    }

    /** Ids des étiquettes { fr, en } (créées ou mises à jour), dans l'ordre reçu. */
    public static function idsFor(array $tags): array
    {
        $ids = [];
        foreach ($tags as $t) {
            $name = is_array($t) ? $t : ['fr' => (string) $t, 'en' => (string) $t];
            $slug = Str::slug($name['fr'] ?: ($name['en'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $tag = static::firstOrNew(['slug' => $slug]);
            $tag->name = ['fr' => $name['fr'] ?? '', 'en' => ($name['en'] ?? '') ?: ($tag->name['en'] ?? '')];
            $tag->save();
            $ids[] = $tag->id;
        }

        return array_values(array_unique($ids));
    }
}
