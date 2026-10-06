<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_only_the_given_projects(): void
    {
        Project::firstWhere('slug', 'ciste')->update(['title' => ['fr' => 'Modifié', 'en' => 'Edited']]);
        Project::firstWhere('slug', 'wura')->update(['title' => ['fr' => 'Gardé', 'en' => 'Kept']]);
        Project::firstWhere('slug', 'megatech')->images()->detach();

        $this->artisan('portfolio:sync-projects', ['slugs' => ['ciste'], '--images-only' => 'megatech'])->assertSuccessful();

        $this->assertSame('CISTE', Project::firstWhere('slug', 'ciste')->title['fr']);
        $this->assertSame('Gardé', Project::firstWhere('slug', 'wura')->title['fr'], 'les autres projets ne sont pas touchés');
        $megatech = Project::firstWhere('slug', 'megatech');
        $this->assertCount(1, $megatech->images);
        $this->assertSame('Site MEGATECH', $megatech->title['fr'], 'ajouter des captures ne vide pas les textes');
    }
}
