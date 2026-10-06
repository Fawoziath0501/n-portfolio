<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\UiLabel;
use App\Models\User;
use App\Support\Portfolio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $data['profile']['photo'] = $this->seedFile('portrait-fawoziath-salou.jpg', 'image');
        $data['profile']['cv'] = $this->seedFile('CV-Fawoziath-SALOU.pdf', 'doc');
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
                unset($item['id']);
                // Pages légales : « key » (page système) n'est pas modifiable depuis l'administration, donc hors $fillable.
                $record = $key === 'legalPages' ? (new $model)->forceFill(['key' => $item['key'] ?? null]) : new $model;
                $record->saveFromFront($item + ['position' => $i]);
            }
        }
    }

    /** Copie un fichier de database/seeders/assets sur le disque public et l'enregistre dans la médiathèque ; renvoie son adresse. */
    private function seedFile(string $name, string $kind): string
    {
        $source = __DIR__.'/assets/'.$name;
        if (! is_file($source)) {
            return '';
        }
        $path = 'media/'.$name;
        Storage::disk('public')->put($path, file_get_contents($source));
        [$w, $h] = $kind === 'image' ? (@getimagesize($source) ?: [0, 0]) : [0, 0];

        return Media::updateOrCreate(
            ['path' => $path],
            ['name' => $name, 'url' => '/storage/'.$path, 'kind' => $kind, 'width' => $w, 'height' => $h, 'size' => filesize($source)]
        )->url;
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
