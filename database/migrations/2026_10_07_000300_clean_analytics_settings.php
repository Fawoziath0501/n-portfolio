<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Statistiques : seuls le suivi intégré et « désactivé » existent réellement. Les anciennes options
 * (Umami, Plausible, GA4, « Search Console connectée ») n'étaient reliées à rien et affichaient des chiffres simulés.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Setting::find('settings')) {
            return;
        }
        $settings = Setting::get('settings');
        $provider = $settings['analytics']['provider'] ?? 'none';
        $settings['analytics'] = ['provider' => $provider === 'local' ? 'local' : 'none'];
        Setting::put('settings', $settings);
    }

    public function down(): void
    {
        //
    }
};
