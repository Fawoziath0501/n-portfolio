<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresse anglaise propre à chaque article (/en/blog/{slug_en}), tirée de son titre anglais.
 * Les anciennes adresses anglaises (slug français) restent valides : elles redirigent vers la nouvelle (301).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('slug_en')->nullable()->unique()->after('slug');
        });

        Post::withTrashed()->whereNull('slug_en')->get()->each(function (Post $p) {
            if (($p->title['en'] ?? '') !== '') {
                $p->forceFill(['slug_en' => Post::uniqueSlug($p->title['en'], $p->id, 'slug_en')])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['slug_en']);
            $table->dropColumn('slug_en');
        });
    }
};
