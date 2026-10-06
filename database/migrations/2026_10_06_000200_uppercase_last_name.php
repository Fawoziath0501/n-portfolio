<?php

use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/** Le nom de famille s'écrit en majuscules : SALOU (profil, titre SEO, compte administrateur). */
return new class extends Migration
{
    public function up(): void
    {
        $this->replace('Salou', 'SALOU');
    }

    public function down(): void
    {
        $this->replace('SALOU', 'Salou');
    }

    private function replace(string $from, string $to): void
    {
        Profile::query()->where('last_name', $from)->update(['last_name' => $to]);
        User::query()->get()->each(fn ($u) => $u->update(['name' => str_replace($from, $to, $u->name)]));

        $seo = Setting::get('seo');
        if (isset($seo['siteTitle']) && is_array($seo['siteTitle'])) {
            $seo['siteTitle'] = array_map(fn ($v) => str_replace(' '.$from.' ', ' '.$to.' ', (string) $v), $seo['siteTitle']);
            Setting::put('seo', $seo);
        }
    }
};
