<?php

use App\Models\Profile;
use App\Models\UiLabel;
use Illuminate\Database\Migrations\Migration;

/**
 * Textes d'interface des pages légales (lien du pied de page, mentions sous les formulaires),
 * ajoutés aux sites déjà installés. Le lien « Admin » du pied de page public est retiré.
 * Les pages elles-mêmes sont dans la table legal_pages (migration suivante).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Profile::query()->exists()) {
            return; // installation neuve : le seeder crée les textes
        }
        foreach (json_decode(file_get_contents(database_path('seeders/data/labels.json')), true) as $i => $label) {
            UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
        }
        UiLabel::where('key', 'adminLink')->delete();
    }

    public function down(): void
    {
        UiLabel::whereIn('key', ['privacyLink', 'privacyUpdated', 'formPrivacy', 'nlPrivacy'])->delete();
    }
};
