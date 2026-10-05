<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Setting;
use App\Models\UiLabel;
use App\Models\User;
use App\Support\Portfolio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Contenu réel du portfolio (repris des maquettes) + compte administrateur.
     * Chaque élément passe par saveFromFront(), qui crée aussi ses relations
     * (technologies, étiquettes, entreprises, missions, compétences, langues…).
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/portfolio.json'), true);

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'saloufawoziath236@gmail.com')],
            ['name' => 'Fawoziath Salou', 'password' => Hash::make(env('ADMIN_PASSWORD', 'password'))]
        );

        (Profile::query()->first() ?? new Profile)->saveFromFront($data['profile']);
        Portfolio::saveHome($data['home']);

        // Textes de l'interface publique (FR / EN).
        foreach (json_decode(file_get_contents(__DIR__.'/data/labels.json'), true) as $i => $label) {
            UiLabel::updateOrCreate(['key' => $label['key']], $label + ['position' => $i]);
        }

        foreach (Portfolio::SETTINGS as $key) {
            Setting::put($key, $data[$key]);
        }

        foreach (Portfolio::COLLECTIONS as $key => $model) {
            $model::withTrashed()->forceDelete();
            foreach ($data[$key] ?? [] as $i => $item) {
                if ($key === 'skillGroups') {
                    $item['code'] = $item['id'];
                }
                unset($item['id']);
                (new $model)->saveFromFront($item + ['position' => $i]);
            }
        }
    }
}
