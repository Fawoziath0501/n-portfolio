<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Chaque test part du contenu réel du portfolio (DatabaseSeeder). */
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Connecte un administrateur. */
    protected function admin(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
