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

    public function test_admin_data_contains_everything_the_back_office_needs(): void
    {
        $this->admin()->getJson('/api/admin/data')
            ->assertOk()
            ->assertJsonStructure(['projects', 'posts', 'profile', 'home', 'seo', 'settings', 'messages', 'subscribers', 'media', 'labels', 'trashCount']);
    }
}
