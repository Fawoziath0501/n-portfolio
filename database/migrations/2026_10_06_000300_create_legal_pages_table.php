<?php

use App\Models\LegalPage;
use App\Models\Profile;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages légales (mentions légales, confidentialité, CGU, cookies…), éditables dans l'administration.
 * « key » repère les pages système (les formulaires pointent vers « privacy ») ; les slugs sont propres à chaque langue.
 * Sur un site déjà installé, les pages par défaut sont créées et la politique de confidentialité existante est reprise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->nullable()->unique()->comment('Page système : legal, privacy, terms, cookies');
            $table->string('slug_fr')->unique();
            $table->string('slug_en')->unique();
            $table->json('title');
            $table->json('body')->nullable();
            $table->boolean('published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        if (! Profile::query()->exists()) {
            return; // installation neuve : le seeder crée les pages
        }
        $privacy = Setting::get('privacy');
        $data = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true);
        foreach ($data['legalPages'] as $i => $page) {
            if ($page['key'] === 'privacy' && ! empty($privacy['body'])) {
                $page['title'] = $privacy['title'] ?? $page['title'];
                $page['body'] = $privacy['body'];
            }
            (new LegalPage)->forceFill(['key' => $page['key']])->saveFromFront($page + ['position' => $i]);
        }
        Setting::whereKey('privacy')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
