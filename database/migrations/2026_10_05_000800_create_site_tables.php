<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['contact', 'service'])->default('contact');
            $table->enum('status', ['new', 'read', 'replied', 'archived'])->default('new');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service')->default('')->comment('Intitulé du service au moment de la demande');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->default('');
            $table->string('subject')->default('');
            $table->text('body');
            $table->string('lang', 2)->default('fr');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique()->comment('Un e-mail retiré puis réinscrit est restauré');
            $table->string('lang', 2)->default('fr');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('msg');
            $table->timestamps();
        });

        // Suivi d'audience intégré (pages vues et clics de contact), sans cookie.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['pv', 'click']);
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
        foreach (['events', 'activities', 'subscribers', 'messages', 'home_sections'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
