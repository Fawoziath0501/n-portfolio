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

    public function test_unpublished_project_is_not_found(): void
    {
        Project::where('slug', 'presentia')->update(['published' => false]);

        $this->get('/fr/projets/presentia')->assertNotFound()->assertDontSee('<title>Présentia', false);
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

    public function test_each_site_page_has_its_own_title_and_description(): void
    {
        $labels = \App\Models\UiLabel::whereIn('key', ['pAbout.1', 'pAbout.2', 'pContact.1'])->get()->keyBy('key');

        $this->get('/fr/a-propos')->assertSee('<title>'.e($labels['pAbout.1']->fr).' | ', false)
            ->assertSee('content="'.e($labels['pAbout.2']->fr).'"', false);
        $this->get('/en/contact')->assertSee('<title>'.e($labels['pContact.1']->en).' | ', false);
    }

    public function test_logged_in_admin_still_sees_the_site_during_maintenance(): void
    {
        \App\Models\Setting::put('settings', ['maintenance' => true] + \App\Models\Setting::get('settings'));

        $this->get('/fr')->assertStatus(503);
        $this->admin()->get('/fr')->assertOk();
        $site = $this->getJson('/api/site')->json('settings');
        $this->assertFalse($site['maintenance']);
        $this->assertTrue($site['maintenancePreview']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/nope')->assertNotFound()->assertJson(['message' => 'Not found']);
    }
}
