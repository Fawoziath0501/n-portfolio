<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contenus « simples » (profil, accueil, SEO, paramètres) stockés en JSON par clé.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('category')->nullable();
            $table->json('role')->nullable();
            $table->json('summary')->nullable();
            $table->json('context')->nullable();
            $table->json('problem')->nullable();
            $table->json('contribution')->nullable();
            $table->json('solution')->nullable();
            $table->json('results')->nullable();
            $table->string('year')->default('');
            $table->string('link')->default('');
            $table->string('repo')->default('');
            $table->json('images')->nullable();
            $table->json('tech')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('tags')->nullable();
            $table->date('date')->nullable();
            $table->unsignedSmallInteger('read_min')->default(1);
            $table->string('url')->default('');
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('company')->default('');
            $table->json('location')->nullable();
            $table->json('role');
            $table->json('start')->nullable();
            $table->json('end')->nullable();
            $table->json('description')->nullable();
            $table->json('duties')->nullable();
            $table->boolean('current')->default(false);
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('education', function (Blueprint $table) {
            $table->id();
            $table->json('degree');
            $table->string('school')->default('');
            $table->string('period')->default('');
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->default('code');
            $table->json('title');
            $table->json('description')->nullable();
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('issuer')->default('');
            $table->string('date')->default('');
            $table->string('verify')->default('');
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->string('company')->default('');
            $table->json('role')->nullable();
            $table->json('quote')->nullable();
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('skill_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->json('label');
            $table->json('skills')->nullable();
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('contact'); // contact | service
            $table->string('status')->default('new');   // new | read | replied | archived
            $table->string('service')->default('');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->default('');
            $table->string('subject')->default('');
            $table->text('body');
            $table->string('lang', 2)->default('fr');
            $table->timestamps();
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('lang', 2)->default('fr');
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->nullable(); // null pour une image distante
            $table->string('url');
            $table->string('kind')->default('image'); // image | doc
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('msg');
            $table->timestamps();
        });

        // Suivi d'audience intégré (pages vues et clics de contact).
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // pv | click
            $table->string('key', 30)->nullable();
            $table->string('path')->nullable();
            $table->string('ref')->nullable();
            $table->string('device', 10)->default('desktop');
            $table->string('sid', 40);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['events', 'activities', 'media', 'subscribers', 'messages', 'skill_groups', 'testimonials', 'certifications', 'services', 'education', 'experiences', 'posts', 'projects', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
