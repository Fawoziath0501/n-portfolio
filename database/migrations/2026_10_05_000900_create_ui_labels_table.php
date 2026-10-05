<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Textes de l'interface publique (menus, titres de sections, formulaires, messages),
 * modifiables depuis l'administration. Les listes utilisent des clés numérotées : nav.0, nav.1…
 * Les valeurs peuvent contenir des variables : {name}, {year}, {count}…
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_labels', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->default('Divers');
            $table->text('fr');
            $table->text('en');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_labels');
    }
};
