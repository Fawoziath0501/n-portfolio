<?php

use App\Models\Post;
use App\Models\UiLabel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page de détail des articles : adresse (/fr/blog/{slug}), contenu enrichi et compteur de lectures.
 * Les articles existants reçoivent un slug tiré de leur titre français.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('id');
            $table->json('body')->nullable()->after('excerpt')->comment('Contenu de l’article (texte enrichi)');
            $table->unsignedInteger('views')->default(0)->after('read_min')->comment('Nombre de lectures de la page de l’article');
        });

        Post::withTrashed()->whereNull('slug')->get()->each(fn (Post $p) => $p->forceFill(['slug' => Post::uniqueSlug($p->title['fr'] ?? '', $p->id)])->saveQuietly());

        // Textes du blog (filtres, tri, lectures…) pour les sites déjà installés.
        if (UiLabel::query()->exists()) {
            foreach (json_decode(file_get_contents(database_path('seeders/data/labels.json')), true) as $i => $label) {
                UiLabel::firstOrCreate(['key' => $label['key']], $label + ['position' => $i]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'body', 'views']);
        });
    }
};
