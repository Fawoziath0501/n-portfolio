<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/** Accusé de réception et confirmation d'inscription : activés par défaut sur les sites déjà installés (réglages existants conservés). */
return new class extends Migration
{
    public function up(): void
    {
        $settings = Setting::get('settings');
        if (! $settings) {
            return;
        }
        Setting::put('settings', $settings + ['autoReply' => true, 'welcomeSubscriber' => true]);
    }

    public function down(): void
    {
        //
    }
};
