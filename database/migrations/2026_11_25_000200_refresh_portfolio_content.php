<?php

use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SkillGroup;
use App\Models\UiLabel;
use App\Support\SeedAssets;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte du contenu (25/11/2026) appliquée aux sites déjà installés, depuis les données de départ :
 * profil, compétences, parcours, projets (textes, statut, captures, ordre), menus, titres SEO et textes d'interface.
 * Les messages, inscrits, articles, statistiques, médias et autres réglages ne sont pas touchés.
 * Une installation neuve reçoit déjà ce contenu par le seeder : rien à faire dans ce cas.
 */
return new class extends Migration
{
    /** Textes d'interface réécrits pour le nouveau positionnement. */
    private const LABELS = ['heroBadge', 'aboutShortTitle', 'aboutShortText', 'pAbout.2', 'expIntro', 'fNow'];

    public function up(): void
    {
        if (! Profile::query()->exists()) {
            return;
        }
        $seed = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true);
        $labels = collect(json_decode(file_get_contents(database_path('seeders/data/labels.json')), true));

        DB::transaction(function () use ($seed, $labels) {
            // Textes d'interface : nouveaux libellés ajoutés, libellés du positionnement réécrits.
            foreach ($labels as $i => $label) {
                $row = UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
                if (in_array($label['key'], self::LABELS, true)) {
                    $row->update(['fr' => $label['fr'], 'en' => $label['en']]);
                }
            }

            $p = $seed['profile'];
            Profile::query()->first()->update(['title' => $p['title'], 'stack' => $p['stack'], 'tagline' => $p['tagline'], 'bio' => $p['bio'], 'phone' => $p['phone']]);

            // Parcours : intitulés harmonisés et missions DOUBOVO, reconnus par entreprise et date de début.
            foreach ($seed['experiences'] as $e) {
                $exp = Experience::query()->whereHas('company', fn ($q) => $q->where('name', $e['company']))->get()
                    ->first(fn ($x) => ($x->start['fr'] ?? '') === ($e['start']['fr'] ?? ''));
                $exp?->saveFromFront(['role' => $e['role'], 'duties' => $e['duties']]);
            }

            // Compétences : nouvelle organisation par domaine (remplace l'ancienne).
            SkillGroup::withTrashed()->get()->each->forceDelete();
            foreach ($seed['skillGroups'] as $i => $g) {
                $g['code'] = $g['id'];
                unset($g['id']);
                (new SkillGroup)->saveFromFront($g + ['position' => $i]);
            }

            // Projets : textes, statut, technologies, captures livrées et ordre d'affichage.
            foreach ($seed['projects'] as $i => $item) {
                unset($item['id']);
                $project = Project::withTrashed()->firstWhere('slug', $item['slug']) ?? new Project;
                $item['images'] = SeedAssets::projectImages($item['slug']) ?: $project->images->pluck('url')->all();
                $project->saveFromFront($item + ['position' => $i]);
            }

            // Premier service : web et mobile (reconnu par son ancien titre ; laissé tel quel s'il a été modifié).
            $service = $seed['services'][0];
            Service::query()->get()->first(fn ($x) => ($x->title['fr'] ?? '') === 'Développement web fullstack')
                ?->update(['title' => $service['title'], 'description' => $service['description']]);

            Setting::put('menus', $seed['menus']);
            Setting::put('seo', array_merge(Setting::get('seo'), ['siteTitle' => $seed['seo']['siteTitle'], 'metaDescription' => $seed['seo']['metaDescription']]));
        });
    }

    public function down(): void
    {
        // Contenu éditorial : restaurer la sauvegarde de la base faite avant le déploiement.
    }
};
