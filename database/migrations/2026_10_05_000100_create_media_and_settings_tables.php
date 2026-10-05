<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Médiathèque : tous les fichiers (portrait, CV, captures) y sont référencés.
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->nullable()->comment('Chemin sur le disque public ; null pour une image distante');
            $table->string('url', 500);
            $table->enum('kind', ['image', 'doc'])->default('image');
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('url');
        });

        // Réglages techniques du site (SEO, statistiques, notifications, maintenance).
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('media');
    }
};
