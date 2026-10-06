<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/** Fichiers livrés avec le projet (database/seeders/assets) : copiés sur le disque public et ajoutés à la médiathèque. */
class SeedAssets
{
    /** Copie un fichier et l'enregistre dans la médiathèque ; renvoie son adresse ('' s'il n'existe pas). */
    public static function file(string $name, string $kind): string
    {
        $source = database_path('seeders/assets/'.$name);
        if (! is_file($source)) {
            return '';
        }
        $path = 'media/'.$name;
        Storage::disk('public')->put($path, file_get_contents($source));
        [$w, $h] = $kind === 'image' ? (@getimagesize($source) ?: [0, 0]) : [0, 0];

        $media = Media::updateOrCreate(
            ['path' => $path],
            ['name' => basename($name), 'url' => '/storage/'.$path, 'kind' => $kind, 'width' => $w, 'height' => $h, 'size' => filesize($source)]
        );
        Thumbs::make($media);

        return $media->url;
    }

    /** Captures d'un projet : assets/projects/{slug}-1.jpg, {slug}-2.jpg… dans l'ordre. */
    public static function projectImages(string $slug): array
    {
        $shots = glob(database_path('seeders/assets/projects/'.$slug.'-*.jpg')) ?: [];
        natsort($shots);

        return array_values(array_filter(array_map(fn ($f) => self::file('projects/'.basename($f), 'image'), $shots)));
    }
}
