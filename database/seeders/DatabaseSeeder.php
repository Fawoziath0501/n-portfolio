<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\UiLabel;
use App\Models\User;
use App\Support\Portfolio;
use App\Support\SeedAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Contenu réel du portfolio (repris des maquettes) + compte administrateur.
     * Chaque élément passe par saveFromFront(), qui crée aussi ses relations
     * (technologies, étiquettes, entreprises, missions, compétences, langues…).
     *
     * En production, sur un site déjà installé, seuls le compte administrateur et les nouveaux textes
     * de l'interface sont ajoutés : le contenu modifié depuis l'administration n'est jamais écrasé.
     */
    public function run(): void
    {
        $this->seedAdmin();

        $labels = json_decode(file_get_contents(__DIR__.'/data/labels.json'), true);
        $data = json_decode(file_get_contents(__DIR__.'/data/portfolio.json'), true);

        if (app()->isProduction() && Profile::query()->exists()) {
            foreach ($labels as $i => $label) {
                UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
            }
            foreach (Portfolio::SETTINGS as $key) {
                if (! Setting::find($key)) {
                    Setting::put($key, $data[$key]);
                }
            }
            if (! LegalPage::withTrashed()->exists()) {
                foreach ($data['legalPages'] as $i => $page) {
                    (new LegalPage)->forceFill(['key' => $page['key']])->saveFromFront($page + ['position' => $i]);
                }
            }
            $this->command?->info('Site déjà installé : contenu conservé, nouveaux textes et réglages ajoutés.');

            return;
        }

        // Photo et CV livrés avec le projet (database/seeders/assets), ajoutés à la médiathèque.
        $data['profile']['photo'] = SeedAssets::file('portrait-fawoziath-salou.jpg', 'image');
        $data['profile']['cv'] = SeedAssets::file('CV-Fawoziath-SALOU.pdf', 'doc');
        (Profile::query()->first() ?? new Profile)->saveFromFront($data['profile']);
        Portfolio::saveHome($data['home']);

        // Textes de l'interface publique (FR / EN).
        foreach ($labels as $i => $label) {
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
                // Captures livrées dans database/seeders/assets/projects/{slug}-1.jpg, {slug}-2.jpg…
                if ($key === 'projects') {
                    $item['images'] = SeedAssets::projectImages($item['slug']) ?: ($item['images'] ?? []);
                }
                unset($item['id']);
                // Pages légales : « key » (page système) n'est pas modifiable depuis l'administration, donc hors $fillable.
                $record = $key === 'legalPages' ? (new $model)->forceFill(['key' => $item['key'] ?? null]) : new $model;
                $record->saveFromFront($item + ['position' => $i]);
            }
        }
    }

    /** Compte administrateur (config/portfolio.php) ; mot de passe obligatoire et non trivial en production. */
    private function seedAdmin(): void
    {
        $admin = config('portfolio.admin');
        $password = (string) $admin['password'];

        if (app()->isProduction() && (strlen($password) < 12 || in_array($password, ['password', 'change-moi'], true))) {
            throw new RuntimeException('ADMIN_PASSWORD doit être défini dans .env (12 caractères minimum) avant de lancer le seeder en production.');
        }

        User::updateOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => Hash::make($password !== '' ? $password : 'password')]
        );
    }
}
