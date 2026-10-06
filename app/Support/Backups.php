<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sauvegardes du site : base de données (copie cohérente) et fichiers de la médiathèque, dans une archive datée.
 * Le fichier .env n'est pas inclus (secrets) : gardez sa clé APP_KEY de côté pour pouvoir relire le mot de passe SMTP.
 */
class Backups
{
    public const KEEP = 14;

    public static function dir(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /** Crée une sauvegarde et renvoie le chemin de l'archive. */
    public static function create(): string
    {
        $stamp = now()->format('Y-m-d-His');
        $tmp = storage_path('app/backup-tmp-'.Str::random(8));
        File::ensureDirectoryExists($tmp);

        try {
            $db = config('database.connections.'.config('database.default'));
            if (($db['driver'] ?? '') !== 'sqlite') {
                throw new RuntimeException('Sauvegarde prévue pour SQLite uniquement.');
            }
            // Copie cohérente même si le site écrit au même moment ; repli sur une copie du fichier
            // (impossible dans une transaction, par exemple pendant les tests).
            try {
                DB::statement('VACUUM INTO ?', [$tmp.'/database.sqlite']);
            } catch (\Throwable) {
                is_file($db['database']) ? copy($db['database'], $tmp.'/database.sqlite') : touch($tmp.'/database.sqlite');
            }

            $media = storage_path('app/public');
            $archive = class_exists(\ZipArchive::class)
                ? self::zip(self::dir()."/sauvegarde-$stamp.zip", $tmp.'/database.sqlite', $media)
                : self::tarGz(self::dir()."/sauvegarde-$stamp.tar", $tmp.'/database.sqlite', $media);
        } finally {
            File::deleteDirectory($tmp);
        }
        self::prune();

        return $archive;
    }

    /** Sauvegardes existantes, de la plus récente à la plus ancienne. */
    public static function list(): array
    {
        return collect(File::files(self::dir()))
            ->filter(fn ($f) => preg_match('/^sauvegarde-[\d-]+\.(zip|tar\.gz)$/', $f->getFilename()))
            ->sortByDesc(fn ($f) => $f->getFilename())
            ->map(fn ($f) => ['name' => $f->getFilename(), 'size' => $f->getSize(), 'date' => date(DATE_ATOM, $f->getMTime())])
            ->values()->all();
    }

    /** Chemin d'une sauvegarde existante (nom contrôlé : pas de chemin arbitraire). */
    public static function path(string $name): ?string
    {
        $path = self::dir().'/'.basename($name);

        return preg_match('/^sauvegarde-[\d-]+\.(zip|tar\.gz)$/', basename($name)) && is_file($path) ? $path : null;
    }

    private static function prune(): void
    {
        foreach (array_slice(self::list(), self::KEEP) as $old) {
            File::delete(self::dir().'/'.$old['name']);
        }
    }

    private static function zip(string $file, string $db, string $media): string
    {
        $zip = new \ZipArchive;
        $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($db, 'database.sqlite');
        foreach (File::allFiles($media) as $f) {
            $zip->addFile($f->getPathname(), 'storage/'.str_replace('\\', '/', $f->getRelativePathname()));
        }
        $zip->close();

        return $file;
    }

    private static function tarGz(string $tar, string $db, string $media): string
    {
        $phar = new \PharData($tar);
        $phar->addFile($db, 'database.sqlite');
        foreach (File::allFiles($media) as $f) {
            $phar->addFile($f->getPathname(), 'storage/'.str_replace('\\', '/', $f->getRelativePathname()));
        }
        $phar->compress(\Phar::GZ);
        unset($phar);
        @unlink($tar);

        return $tar.'.gz';
    }
}
