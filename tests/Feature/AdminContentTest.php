<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    private function project(array $overrides = []): array
    {
        return $overrides + [
            'slug' => 'nouveau-projet',
            'title' => ['fr' => 'Nouveau projet', 'en' => 'New project'],
            'summary' => ['fr' => 'Résumé', 'en' => 'Summary'],
            'published' => true,
            'tech' => ['Laravel', 'Vue.js'],
        ];
    }

    public function test_create_project_with_technologies_at_given_position(): void
    {
        $first = Project::ordered()->first();

        $res = $this->admin()->postJson('/api/admin/projects', [
            'item' => $this->project(),
            'order' => [0, $first->id],
            'activity' => 'Projet ajouté',
        ])->assertCreated()->assertJsonPath('tech', ['Laravel', 'Vue.js']);

        $this->assertSame($res->json('id'), Project::ordered()->first()->id);
        $this->assertDatabaseHas('activities', ['msg' => 'Projet ajouté']);
    }

    public function test_slug_must_be_valid_and_unique_even_in_trash(): void
    {
        $this->admin();

        $this->postJson('/api/admin/projects', ['item' => $this->project(['slug' => 'Pas Bon'])])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->postJson('/api/admin/projects', ['item' => $this->project(['slug' => 'presentia'])])
            ->assertUnprocessable()->assertJsonPath('errors.slug.0', 'Ce slug est déjà utilisé par un autre projet.');

        Project::where('slug', 'presentia')->first()->delete();
        $this->postJson('/api/admin/projects', ['item' => $this->project(['slug' => 'presentia'])])
            ->assertUnprocessable()->assertJsonPath('errors.slug.0', fn ($m) => str_contains($m, 'corbeille'));
    }

    public function test_update_keeps_its_own_slug(): void
    {
        $project = Project::where('slug', 'presentia')->first();

        $this->admin()->putJson("/api/admin/projects/{$project->id}", [
            'item' => ['slug' => 'presentia', 'title' => ['fr' => 'Présentia 2', 'en' => 'Presentia 2'], 'createdAt' => 'ignoré'],
        ])->assertOk()->assertJsonPath('title.fr', 'Présentia 2');
    }

    public function test_reorder(): void
    {
        $ids = Project::ordered()->pluck('id')->reverse()->values()->all();

        $this->admin()->postJson('/api/admin/projects/reorder', ['ids' => $ids])->assertNoContent();

        $this->assertSame($ids, Project::ordered()->pluck('id')->all());
    }

    public function test_destroy_moves_to_trash(): void
    {
        $project = Project::first();

        $this->admin()->deleteJson("/api/admin/projects/{$project->id}")->assertOk()->assertJson(['trashCount' => 1]);

        $this->assertSoftDeleted($project);
        $this->assertNotContains($project->slug, array_column($this->getJson('/api/site')->json('projects'), 'slug'));
    }

    public function test_unknown_collection_returns_404(): void
    {
        $this->admin()->postJson('/api/admin/users', ['item' => []])->assertNotFound();
    }

    public function test_documents(): void
    {
        $this->admin();

        $this->putJson('/api/admin/documents/profile', ['value' => ['firstName' => '', 'email' => 'x']])
            ->assertUnprocessable()->assertJsonValidationErrors(['firstName', 'lastName', 'email']);

        $profile = $this->getJson('/api/admin/data')->json('profile');
        $profile['firstName'] = 'Fawo';
        $this->putJson('/api/admin/documents/profile', ['value' => $profile])->assertOk()->assertJsonPath('firstName', 'Fawo');
        $this->assertSame('Fawo', Profile::current()->first_name);

        $this->putJson('/api/admin/documents/settings', ['value' => ['maintenance' => true] + Setting::get('settings')])->assertOk();
        $this->assertTrue(Setting::get('settings')['maintenance']);

        $this->putJson('/api/admin/documents/inconnu', ['value' => ['a' => 1]])->assertNotFound();
    }
}
