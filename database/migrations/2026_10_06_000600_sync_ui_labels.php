<?php

use App\Models\Profile;
use App\Models\UiLabel;
use Illuminate\Database\Migrations\Migration;

/** Ajoute aux sites déjà installés les textes d'interface apparus depuis (ex. « Article précédent »), sans modifier les existants. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Profile::query()->exists()) {
            return;
        }
        foreach (json_decode(file_get_contents(database_path('seeders/data/labels.json')), true) as $i => $label) {
            UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
        }
    }

    public function down(): void
    {
        //
    }
};
