<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Support\Thumbs;
use Illuminate\Console\Command;

/** Crée les versions allégées des images (seulement celles qui n'en ont pas encore, sauf --all). */
class MakeThumbs extends Command
{
    protected $signature = 'portfolio:thumbs {--all : recréer aussi celles qui existent déjà}';

    protected $description = 'Crée les versions WebP allégées des images de la médiathèque';

    public function handle(): int
    {
        $n = 0;
        Media::query()->where('kind', 'image')->whereNotNull('path')
            ->when(! $this->option('all'), fn ($q) => $q->whereNull('variants'))
            ->each(function (Media $m) use (&$n) {
                Thumbs::make($m);
                $n += $m->variants ? 1 : 0;
            });
        $this->info($n.' image(s) allégée(s)');

        return self::SUCCESS;
    }
}
