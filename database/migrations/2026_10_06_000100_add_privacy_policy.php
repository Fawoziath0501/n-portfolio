<?php

use App\Models\Setting;
use App\Models\UiLabel;
use Illuminate\Database\Migrations\Migration;

/**
 * Politique de confidentialité (réglage « privacy », éditable dans l'administration)
 * et textes d'interface associés, ajoutés aux sites déjà installés sans toucher au contenu existant.
 * Le lien « Admin » du pied de page public est retiré.
 */
return new class extends Migration
{
    public function up(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true);
        if (! Setting::find('privacy')) {
            Setting::put('privacy', $data['privacy']);
        }

        foreach (json_decode(file_get_contents(database_path('seeders/data/labels.json')), true) as $i => $label) {
            UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
        }
        UiLabel::where('key', 'adminLink')->delete();
    }

    public function down(): void
    {
        Setting::whereKey('privacy')->delete();
        UiLabel::whereIn('key', ['privacyLink', 'privacyUpdated', 'formPrivacy', 'nlPrivacy'])->delete();
    }
};
