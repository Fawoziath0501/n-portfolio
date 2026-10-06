<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Support\SeedAssets;
use Illuminate\Console\Command;

/**
 * Met à jour des projets précis à partir des données de départ (database/seeders/data/portfolio.json)
 * et de leurs captures (database/seeders/assets/projects), sans toucher au reste du site.
 * Exemple : php artisan portfolio:sync-projects ciste logiciste --images-only=wisafrica,megatech
 */
class SyncProjects extends Command
{
    protected $signature = 'portfolio:sync-projects {slugs* : projets à reprendre entièrement (textes et captures)} {--images-only= : projets dont seules les captures sont ajoutées (liste séparée par des virgules)}';

    protected $description = 'Met à jour des projets depuis les données de départ, sans réinitialiser la base';

    public function handle(): int
    {
        $seed = collect(json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true)['projects']);
        $imagesOnly = array_filter(explode(',', (string) $this->option('images-only')));

        foreach ($this->argument('slugs') as $slug) {
            $item = $seed->firstWhere('slug', $slug);
            if (! $item) {
                $this->warn("$slug : absent des données de départ");

                continue;
            }
            unset($item['id']);
            $item['images'] = SeedAssets::projectImages($slug) ?: ($item['images'] ?? []);
            $project = Project::withTrashed()->firstWhere('slug', $slug);
            if (! $project) {
                $item['position'] = $seed->search(fn ($x) => $x['slug'] === $slug);
            }
            ($project ?? new Project)->saveFromFront($item);
            $this->info("$slug : mis à jour");
        }

        foreach ($imagesOnly as $slug) {
            $project = Project::firstWhere('slug', trim($slug));
            $images = SeedAssets::projectImages(trim($slug));
            if ($project && $images) {
                $project->saveFromFront(['images' => $images]);
                $this->info("$slug : ".count($images).' capture(s)');
            } else {
                $this->warn("$slug : projet ou captures introuvables");
            }
        }

        return self::SUCCESS;
    }
}
