<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->json('label');
            $table->string('icon')->default('code');
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_group_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('note')->nullable();
            $table->string('logo')->nullable()->comment('Chemin du logo (SVG)');
            $table->string('icon')->nullable()->comment('Icône Material Symbols si pas de logo');
            $table->unsignedTinyInteger('hero_position')->nullable()->comment('Ordre dans la carte « Stack » du hero ; null = non affichée');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
        Schema::dropIfExists('skill_groups');
    }
};
