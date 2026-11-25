<?php

use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute aux sites installés le groupe « Déploiement & environnement » (Docker), Docker dans la stack de Doubovo
 * et dans la biographie, depuis les données de départ. Les autres groupes et projets ne sont pas touchés.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Profile::query()->exists()) {
            return;
        }
        $seed = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true);

        DB::transaction(function () use ($seed) {
            $codes = array_column($seed['skillGroups'], 'id');
            $group = collect($seed['skillGroups'])->firstWhere('id', 'sg10');
            if (! SkillGroup::withTrashed()->where('code', 'sg10')->exists()) {
                $group['code'] = $group['id'];
                unset($group['id']);
                (new SkillGroup)->saveFromFront($group);
            }
            // Ordre des groupes : celui des données de départ ; les groupes ajoutés depuis l'administration restent à la fin.
            foreach (SkillGroup::withTrashed()->get() as $g) {
                $pos = array_search($g->code, $codes, true);
                $g->update(['position' => $pos === false ? count($codes) + $g->position : $pos]);
            }

            $doubovo = collect($seed['projects'])->firstWhere('slug', 'doubovo');
            Project::firstWhere('slug', 'doubovo')?->saveFromFront(['tech' => $doubovo['tech']]);

            $profile = Profile::query()->first();
            if (! str_contains($profile->bio['fr'] ?? '', 'Docker')) {
                $profile->update(['bio' => $seed['profile']['bio']]);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
