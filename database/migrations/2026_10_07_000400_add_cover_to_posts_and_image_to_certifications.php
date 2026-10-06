<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Image de couverture des articles et photo des certificats (fichiers de la médiathèque).
 * La photo d'un certificat n'est jamais servie telle quelle au public : seulement un aperçu réduit et filigrané.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('cover_id')->nullable()->after('icon')->constrained('media')->nullOnDelete();
        });
        Schema::table('certifications', function (Blueprint $table) {
            $table->foreignId('image_id')->nullable()->after('verify')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropConstrainedForeignId('cover_id'));
        Schema::table('certifications', fn (Blueprint $table) => $table->dropConstrainedForeignId('image_id'));
    }
};
