<?php

use App\Models\Media;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Les fichiers importés avaient une adresse absolue figée sur APP_URL (ex. http://localhost/storage/…),
 * introuvable dès que le site est servi ailleurs (127.0.0.1:8000, domaine de production) : 404.
 * Elles deviennent relatives (/storage/…), ainsi que les images des réglages SEO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Media::withTrashed()->whereNotNull('path')->each(function (Media $m) {
            $relative = '/storage/'.ltrim($m->path, '/');
            if ($m->url !== $relative && str_ends_with($m->url, $relative)) {
                $m->forceFill(['url' => $relative])->saveQuietly();
            }
        });

        $seo = Setting::get('seo');
        foreach (['ogImage', 'favicon'] as $key) {
            if (! empty($seo[$key]) && is_string($seo[$key])) {
                $seo[$key] = preg_replace('#^https?://[^/]+(?=/storage/)#', '', $seo[$key]);
            }
        }
        if ($seo) {
            Setting::put('seo', $seo);
        }
    }

    public function down(): void
    {
        //
    }
};
