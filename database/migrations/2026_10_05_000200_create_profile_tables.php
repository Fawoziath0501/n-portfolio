<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil de la propriétaire du portfolio et ses éléments liés.
 * Les champs « json » traduisibles ont la forme { "fr": "…", "en": "…" }.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->default('');
            $table->string('last_name');
            $table->json('title');
            $table->json('stack')->nullable();
            $table->json('tagline')->nullable();
            $table->json('bio')->nullable();
            $table->json('location')->nullable();
            $table->string('email');
            $table->string('phone')->default('');
            $table->json('hours')->nullable();
            $table->unsignedSmallInteger('since')->nullable()->comment('Année de début d’activité');
            $table->json('availability')->nullable();
            $table->json('availability_short')->nullable();
            $table->json('status')->nullable();
            $table->json('reply_time')->nullable();
            $table->json('reply_delay')->nullable();
            $table->json('formats')->nullable();
            $table->json('zone')->nullable();
            $table->json('soft_skills')->nullable();
            $table->json('cta_primary')->nullable();
            $table->json('cta_secondary')->nullable();
            $table->foreignId('photo_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('cv_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('level')->nullable();
            $table->string('cefr', 4)->nullable()->comment('Niveau CECRL, ex. A2');
            $table->boolean('featured')->default(false)->comment('Affichée dans les résumés');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('icon')->default('star');
            $table->json('title');
            $table->json('text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('value_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_value_id')->constrained()->cascadeOnDelete();
            $table->json('label');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('icon')->default('link');
            $table->string('url', 500)->default('');
            $table->string('handle')->default('');
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['social_links', 'value_keywords', 'profile_values', 'languages', 'profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
