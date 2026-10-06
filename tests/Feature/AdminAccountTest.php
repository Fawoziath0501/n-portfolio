<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    public function test_password_change_requires_current_password_and_a_strong_new_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('ancien-mot-2024')]);
        $this->actingAs($user);
        $base = ['name' => $user->name, 'email' => $user->email];

        $this->putJson('/api/admin/account', $base + ['current_password' => 'faux', 'password' => 'NouveauMot2026x', 'password_confirmation' => 'NouveauMot2026x'])
            ->assertUnprocessable()->assertJsonPath('errors.current_password.0', 'Le mot de passe actuel est incorrect.');
        $this->putJson('/api/admin/account', $base + ['current_password' => 'ancien-mot-2024', 'password' => 'court1', 'password_confirmation' => 'court1'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/api/admin/account', $base + ['current_password' => 'ancien-mot-2024', 'password' => 'NouveauMot2026x', 'password_confirmation' => 'autre'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->putJson('/api/admin/account', $base + ['current_password' => 'ancien-mot-2024', 'password' => 'NouveauMot2026x', 'password_confirmation' => 'NouveauMot2026x'])
            ->assertOk();
        $this->assertTrue(Hash::check('NouveauMot2026x', $user->fresh()->password));
        $this->assertDatabaseHas('activities', ['msg' => 'Mot de passe modifié']);
    }

    public function test_name_and_email_can_change_but_email_stays_unique(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->putJson('/api/admin/account', ['name' => 'Fawo', 'email' => $other->email, 'current_password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->putJson('/api/admin/account', ['name' => 'Fawo', 'email' => 'nouveau@example.com', 'current_password' => 'password'])
            ->assertOk()->assertJsonPath('user.email', 'nouveau@example.com');
        $this->assertTrue(Hash::check('password', $user->fresh()->password), 'mot de passe inchangé si le champ est vide');
    }

    public function test_account_requires_authentication(): void
    {
        $this->putJson('/api/admin/account', [])->assertUnauthorized();
    }

    public function test_remember_me_is_opt_in(): void
    {
        $user = User::factory()->create();

        $res = $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->assertEmpty(array_filter($res->headers->getCookies(), fn ($c) => str_starts_with($c->getName(), 'remember_web_')));

        $this->postJson('/api/admin/logout');
        $res = $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])->assertOk();
        $this->assertNotEmpty(array_filter($res->headers->getCookies(), fn ($c) => str_starts_with($c->getName(), 'remember_web_')));
    }

    public function test_security_headers(): void
    {
        $this->get('/fr')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->get('/admin')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertSee('noindex', false);
    }
}
