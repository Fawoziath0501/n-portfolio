<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrottleTest extends TestCase
{
    use RefreshDatabase;

    /** Chaque limite a son propre compteur : naviguer sur le site ne bloque pas l'envoi d'un message. */
    public function test_page_views_do_not_consume_the_contact_form_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/track', ['t' => 'pv', 'path' => '/fr', 'sid' => 'visite-test']);
        }

        $this->postJson('/api/messages', ['type' => 'contact', 'name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Bonjour, un projet à discuter.'])
            ->assertCreated();
    }
}
