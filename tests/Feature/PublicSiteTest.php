<?php

namespace Tests\Feature;

use App\Models\Project;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_home_pages_render_in_both_languages(): void
    {
        $this->get('/')->assertOk()->assertSee('<html lang="fr"', false);
        $this->get('/en')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_project_page_uses_project_title_and_summary(): void
    {
        $project = Project::where('slug', 'presentia')->firstOrFail();

        $this->get('/fr/projets/presentia')
            ->assertOk()
            ->assertSee('<title>'.e($project->title['fr']).' | ', false)
            ->assertSee('content="'.e($project->summary['fr']).'"', false);
    }

    public function test_unpublished_project_falls_back_to_site_title(): void
    {
        Project::where('slug', 'presentia')->update(['published' => false]);

        $this->get('/fr/projets/presentia')->assertOk()->assertDontSee('<title>Présentia', false);
    }

    public function test_site_api_exposes_published_content_and_labels(): void
    {
        Project::where('slug', 'ciste')->update(['published' => false]);

        $data = $this->getJson('/api/site')->assertOk()->json();

        $this->assertNotContains('ciste', array_column($data['projects'], 'slug'));
        $this->assertContains('presentia', array_column($data['projects'], 'slug'));
        $this->assertNotEmpty($data['labels']);
        $this->assertArrayHasKey('fr', reset($data['labels']));
        $this->assertArrayNotHasKey('notifyEmail', $data['settings']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/nope')->assertNotFound()->assertJson(['message' => 'Not found']);
    }
}
