<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use App\Models\Concerns\Trashable;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use ContentModel, Trashable;

    /** Champs saisis avec l'éditeur de texte enrichi (HTML nettoyé). */
    protected array $rich = ['body'];

    // « views » n'est pas modifiable depuis l'administration : il est incrémenté à chaque lecture.
    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'icon', 'date', 'read_min', 'url', 'published', 'position'];

    protected $with = ['tags', 'cover'];

    protected function casts(): array
    {
        return ['title' => 'array', 'excerpt' => 'array', 'body' => 'array', 'date' => 'date:Y-m-d', 'read_min' => 'integer', 'views' => 'integer', 'published' => 'boolean', 'newsletter_sent_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            // Adresse générée depuis le titre si elle est vide.
            if (! $post->slug) {
                $post->slug = static::uniqueSlug($post->title['fr'] ?? ($post->title['en'] ?? ''), $post->id);
            }
            // Temps de lecture calculé depuis le contenu (environ 220 mots par minute).
            if ($post->isDirty('body') && ($words = str_word_count(strip_tags(Portfolio::tx($post->body)), 0, 'À..ÿ')) > 0) {
                $post->read_min = max(1, (int) ceil($words / 220));
            }
        });
    }

    /** Slug libre tiré d'un titre (suffixe numérique si déjà pris, corbeille comprise). */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        for ($n = 2; static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withPivot('position')->orderByPivot('position');
    }

    /** Image de partage : la couverture choisie, sinon l’image générée aux couleurs du site (adresse absolue). */
    public function shareImageUrl(string $lang = 'fr'): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $cover = $this->cover?->url;
        if ($cover) {
            return str_starts_with($cover, 'http') ? $cover : $base.'/'.ltrim($cover, '/');
        }

        return $base.'/blog/'.$this->slug.'/couverture-'.($lang === 'en' ? 'en' : 'fr').'.png';
    }

    /** Image de couverture (médiathèque). */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'position', 'cover_id'];
    }

    protected function frontRelations(): array
    {
        return ['tags' => $this->tags->pluck('name')->all(), 'cover' => $this->cover?->url ?? ''];
    }

    protected function syncFrontRelations(array $data): void
    {
        $this->linkMedia('cover', 'cover', $data);
        if (isset($data['tags'])) {
            $ids = Tag::idsFor($data['tags']);
            $this->tags()->sync(collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
        }
    }

    public function trashLabel(): string
    {
        return Portfolio::tx($this->title);
    }

    public function trashMeta(): string
    {
        return (string) $this->date?->format('Y-m-d');
    }
}
