<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\UiLabel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SeederTest extends TestCase
{
    public function test_production_requires_a_strong_admin_password(): void
    {
        $this->app['env'] = 'production';
        config(['portfolio.admin.password' => 'password']);

        $this->expectException(RuntimeException::class);
        $this->artisan('db:seed', ['--force' => true]);
    }

    public function test_production_reseed_keeps_existing_content(): void
    {
        $this->app['env'] = 'production';
        config(['portfolio.admin.email' => 'admin@example.com', 'portfolio.admin.password' => 'un-mot-de-passe-solide']);

        Project::where('slug', 'presentia')->update(['year' => '2099']);
        $edited = UiLabel::first();
        $edited->update(['fr' => 'Texte modifié']);
        UiLabel::orderByDesc('position')->first()->delete();
        $labels = UiLabel::count();

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertSame('2099', Project::where('slug', 'presentia')->value('year'));
        $this->assertSame('Texte modifié', $edited->fresh()->fr);
        $this->assertSame($labels + 1, UiLabel::count());
        $this->assertTrue(Hash::check('un-mot-de-passe-solide', User::where('email', 'admin@example.com')->value('password')));
    }
}
