<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrenchMessagesTest extends TestCase
{
    use RefreshDatabase;

    /** En production le site est en français : les erreurs ne doivent jamais afficher une clé brute (validation.xxx). */
    public function test_validation_messages_are_translated_in_french(): void
    {
        app()->setLocale('fr');
        $user = User::factory()->create(['password' => bcrypt('ancien-mot-de-passe-1')]);

        $errors = $this->actingAs($user)->putJson('/api/admin/account', [
            'name' => $user->name, 'email' => $user->email, 'current_password' => 'ancien-mot-de-passe-1',
            'password' => 'nouveau-mot-de-passe-2', 'password_confirmation' => 'autre-chose-3',
        ])->assertUnprocessable()->json('errors');

        $this->assertSame('Les deux mots de passe ne correspondent pas.', $errors['password'][0]);
        $this->assertStringNotContainsString('validation.', json_encode($errors));
        $this->assertSame('Le champ e-mail est obligatoire.', __('validation.required', ['attribute' => __('validation.attributes.email')]));
    }
}
