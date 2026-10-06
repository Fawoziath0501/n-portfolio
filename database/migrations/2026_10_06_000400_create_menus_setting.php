<?php

use App\Models\Profile;
use App\Models\Setting;
use App\Models\UiLabel;
use App\Support\Menus;
use Illuminate\Database\Migrations\Migration;

/**
 * Menus éditables (barre du haut, colonnes du pied de page) : créés à partir des textes actuels de l'interface,
 * puis ces textes, devenus inutiles, sont retirés de « Textes du site ».
 */
return new class extends Migration
{
    private const OBSOLETE = ['nav.0', 'nav.1', 'nav.2', 'nav.3', 'nav.4', 'nav2', 'footNavTitle', 'resources'];

    public function up(): void
    {
        if (! Profile::query()->exists()) {
            return; // installation neuve : le seeder crée les menus et les textes
        }
        if (! Setting::find('menus')) {
            $labels = UiLabel::all()->keyBy('key');
            Setting::put('menus', Menus::defaults(fn ($k) => ['fr' => (string) $labels->get($k)?->fr, 'en' => (string) $labels->get($k)?->en]));
        }
        UiLabel::whereIn('key', self::OBSOLETE)->delete();

        // Nouveaux textes de l'interface (ex. « Informations légales »).
        foreach (json_decode(file_get_contents(database_path('seeders/data/labels.json')), true) as $i => $label) {
            UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
        }
    }

    public function down(): void
    {
        Setting::whereKey('menus')->delete();
    }
};
