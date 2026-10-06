<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Réponses envoyées depuis l'administration (e-mail ou WhatsApp), pour garder l'historique des échanges. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->comment('email ou whatsapp');
            $table->string('subject')->default('');
            $table->text('body');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_replies');
    }
};
