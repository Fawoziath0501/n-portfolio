<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    public function test_admin_api_requires_authentication(): void
    {
        $this->getJson('/api/admin/data')->assertUnauthorized();
        $this->putJson('/api/admin/labels', ['items' => []])->assertUnauthorized();
        $this->getJson('/api/admin/me')->assertOk()->assertJson(['user' => null]);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'mauvais'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.email', $user->email);
        $this->assertAuthenticatedAs($user);

        $this->postJson('/api/admin/logout')->assertNoContent();
        $this->assertGuest();
    }

    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'mauvais'.$i])->assertUnprocessable();
        }
        // Même avec le bon mot de passe : bloqué tant que le délai n'est pas écoulé.
        $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429)->assertJsonPath('errors.email.0', fn ($m) => str_contains($m, 'Trop de tentatives'));
        $this->assertGuest();
        $this->assertDatabaseHas('activities', ['msg' => 'Tentative de connexion échouée ('.$user->email.', IP 127.0.0.1)']);

        $this->travel(16)->minutes();
        $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    }

    public function test_admin_page_is_served_at_the_configured_path_only(): void
    {
        $this->get('/admin')->assertOk()->assertSee('window.__ADMIN_BASE__ = "\/admin"', false);
        $this->assertSame('admin', config('portfolio.admin.path'));
    }

    public function test_admin_data_contains_everything_the_back_office_needs(): void
    {
        $this->admin()->getJson('/api/admin/data')
            ->assertOk()
            ->assertJsonStructure(['projects', 'posts', 'profile', 'home', 'seo', 'settings', 'messages', 'subscribers', 'media', 'labels', 'trashCount']);
    }
}
