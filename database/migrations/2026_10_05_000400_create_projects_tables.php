<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->comment('Unique y compris dans la corbeille');
            $table->json('title');
            $table->json('category')->nullable();
            $table->json('role')->nullable();
            $table->json('summary')->nullable();
            $table->json('context')->nullable();
            $table->json('problem')->nullable();
            $table->json('contribution')->nullable()->comment('Un point par ligne');
            $table->json('solution')->nullable()->comment('Un point par ligne');
            $table->json('results')->nullable();
            $table->string('year', 10)->default('');
            $table->string('link', 500)->default('');
            $table->string('repo', 500)->default('');
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['published', 'position']);
        });

        Schema::create('project_technology', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['project_id', 'technology_id']);
        });

        // Captures d'écran d'un projet (fichiers de la médiathèque).
        Schema::create('project_media', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['project_id', 'media_id']);
        });
    }

    public function down(): void
    {
        foreach (['project_media', 'project_technology', 'projects', 'technologies'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
