<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use App\Support\Portfolio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Contenu réel du portfolio (repris des maquettes) + compte administrateur.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/portfolio.json'), true);

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'saloufawoziath236@gmail.com')],
            ['name' => 'Fawoziath Salou', 'password' => Hash::make(env('ADMIN_PASSWORD', 'password'))]
        );

        $sections = collect($data['home']['sections']);
        if (! $sections->contains('type', 'blog')) {
            $sections->splice($sections->count() - 1, 0, [['id' => 'blog', 'type' => 'blog', 'enabled' => true]]);
            $data['home']['sections'] = $sections->values()->all();
        }

        foreach (Portfolio::DOCUMENTS as $doc) {
            Setting::put($doc, $data[$doc]);
        }

        foreach (Portfolio::COLLECTIONS as $key => $model) {
            $model::query()->delete();
            foreach ($data[$key] ?? [] as $i => $item) {
                if ($key === 'skillGroups') {
                    $item['code'] = $item['id'];
                }
                unset($item['id']);
                (new $model)->fillFromFront($item + ['position' => $i])->save();
            }
        }
    }
}
