<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Versions allégées des images (WebP, 480 et 960 px de large) : le navigateur choisit la plus adaptée à l'écran
 * (attribut srcset). Les originaux ne sont jamais modifiés.
 */
class Thumbs
{
    public const WIDTHS = [480, 960];

    public static function make(Media $media): void
    {
        $disk = Storage::disk('public');
        if ($media->kind !== 'image' || ! $media->path || ! $disk->exists($media->path) || ! function_exists('imagewebp')) {
            return;
        }
        $src = @imagecreatefromstring($disk->get($media->path));
        if (! $src) {
            return;
        }
        imagepalettetotruecolor($src);
        [$w, $h] = [imagesx($src), imagesy($src)];
        $variants = [];
        foreach (self::WIDTHS as $tw) {
            if ($w <= $tw) {
                continue; // inutile : l’original n’est pas plus grand
            }
            $th = (int) round($h * $tw / $w);
            $img = imagecreatetruecolor($tw, $th);
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagecopyresampled($img, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
            ob_start();
            imagewebp($img, null, 80);
            $path = 'media/thumbs/'.pathinfo($media->path, PATHINFO_FILENAME).'-'.$tw.'.webp';
            $disk->put($path, (string) ob_get_clean());
            imagedestroy($img);
            $variants[$tw] = '/storage/'.$path;
        }
        imagedestroy($src);
        $media->forceFill(['variants' => $variants ?: null, 'width' => $media->width ?: $w, 'height' => $media->height ?: $h])->saveQuietly();
    }

    /** Fichiers des versions allégées (pour les effacer avec l'original). */
    public static function paths(Media $media): array
    {
        return array_map(fn ($u) => preg_replace('#^/storage/#', '', $u), array_values((array) $media->variants));
    }

    /** Correspondance adresse → { w: largeur d'origine, v: { 480: …, 960: … } } pour le site public. */
    public static function map(): array
    {
        return Media::query()->whereNotNull('variants')->get(['url', 'width', 'variants'])
            ->mapWithKeys(fn ($m) => [$m->url => ['w' => (int) $m->width, 'v' => $m->variants]])->all();
    }
}
