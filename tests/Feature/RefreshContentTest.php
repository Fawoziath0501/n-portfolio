<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SkillGroup;
use App\Models\UiLabel;
use Tests\TestCase;

/** Migration de contenu du 25/11/2026 : un site installé avec l'ancien contenu reçoit la refonte sans perdre le reste. */
class RefreshContentTest extends TestCase
{
    private function runMigration(): void
    {
        (require database_path('migrations/2026_11_25_000200_refresh_portfolio_content.php'))->up();
    }

    public function test_an_installed_site_receives_the_new_content(): void
    {
        // État « avant » : comme le site en ligne avant la refonte.
        Project::firstWhere('slug', 'portfolio')->forceDelete();
        Project::firstWhere('slug', 'logiciste')->saveFromFront(['images' => ['/storage/media/projects/ciste-1.jpg']]);
        Project::firstWhere('slug', 'wura')->update(['link' => 'https://www.wura.africa', 'status' => '', 'position' => 0]);
        UiLabel::where('key', 'expIntro')->update(['fr' => 'Quatre postes en développement web fullstack.']);
        UiLabel::where('key', 'kFree')->delete();
        Profile::query()->update(['phone' => '+229 01 97 36 32 97']);
        $menus = Setting::get('menus');
        $menus['header']['items'] = array_values(array_filter($menus['header']['items'], fn ($i) => $i['page'] !== 'blog'));
        Setting::put('menus', $menus);
        SkillGroup::query()->get()->each->forceDelete();
        (new SkillGroup)->saveFromFront(['code' => 'sg5', 'label' => ['fr' => 'Outils', 'en' => 'Tools'], 'visible' => true, 'skills' => [['name' => 'Pack Office']]]);
        $message = Message::create(['name' => 'Client', 'email' => 'client@example.com', 'body' => 'Bonjour, un projet à vous proposer.']);
        $posts = Post::count();
        $service = Service::ordered()->first();
        $service->update(['title' => ['fr' => 'Développement web fullstack', 'en' => 'Fullstack web development']]);

        $this->runMigration();

        $this->assertSame('Développement web et mobile full stack', $service->fresh()->title['fr']);

        $seed = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true);
        $this->assertSame(array_column($seed['projects'], 'slug'), Project::ordered()->pluck('slug')->all(), 'ordre des projets');
        $this->assertSame('live', Project::firstWhere('slug', 'portfolio')->status);
        $logiciste = Project::firstWhere('slug', 'logiciste')->images->pluck('url')->all();
        $this->assertCount(5, $logiciste);
        $this->assertStringEndsWith('logiciste-1.jpg', $logiciste[0]);
        $wura = Project::firstWhere('slug', 'wura');
        $this->assertSame(['', 'offline'], [$wura->link, $wura->status], 'lien retiré, site hors ligne');

        $this->assertStringStartsWith('Mes postes', UiLabel::firstWhere('key', 'expIntro')->fr);
        $this->assertSame('Expérience · Freelance', UiLabel::firstWhere('key', 'kFree')->fr);
        $this->assertSame('+229 01 59 97 08 90', Profile::first()->phone);
        $this->assertContains('blog', array_column(Setting::get('menus')['header']['items'], 'page'));
        $this->assertSame(0, Skill::query()->whereJsonContains('name->fr', 'Pack Office')->count());
        $this->assertSame(count($seed['skillGroups']), SkillGroup::count());

        $this->assertModelExists($message);
        $this->assertSame($posts, Post::count(), 'les articles ne sont pas touchés');
    }

    public function test_deployment_skills_are_added_to_an_installed_site(): void
    {
        // État « avant » : site déjà passé par la refonte, sans Docker.
        SkillGroup::firstWhere('code', 'sg10')->forceDelete();
        Project::firstWhere('slug', 'doubovo')->saveFromFront(['tech' => ['Flutter', 'Angular']]);
        $profile = Profile::first();
        $profile->update(['bio' => ['fr' => '<p>Bio sans conteneurs.</p>', 'en' => '<p>Bio.</p>']]);

        (require database_path('migrations/2026_11_25_000300_add_deployment_skills.php'))->up();
        (require database_path('migrations/2026_11_25_000300_add_deployment_skills.php'))->up(); // relancée : aucun doublon

        $seed = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true);
        $this->assertSame(array_column($seed['skillGroups'], 'id'), SkillGroup::ordered()->pluck('code')->all());
        $this->assertSame('/images/devicons/dv_docker.svg', Skill::query()->whereJsonContains('name->fr', 'Docker')->first()->logo);
        $this->assertContains('Docker', Project::firstWhere('slug', 'doubovo')->technologies->pluck('name')->all());
        $this->assertStringContainsString('Docker', $profile->fresh()->bio['fr']);
    }

    public function test_the_public_site_shows_status_freelance_badge_and_project_share_image(): void
    {
        $data = $this->getJson('/api/site')->json();
        $this->assertSame('offline', collect($data['projects'])->firstWhere('slug', 'forbmit')['status']);
        $this->assertSame('Expérience · Freelance', $data['labels']['kFree']['fr']);

        $this->get('/fr/projets/logiciste')->assertOk()
            ->assertSee('<meta property="og:image" content="'.url('/storage/media/projects/logiciste-1.jpg').'"', false);
        $this->get('/fr/projets/portfolio')->assertOk()->assertSee('Portfolio');

        // Données structurées : ville et pays séparés, employeurs actuels, pas de lien WhatsApp parmi les profils.
        $html = $this->get('/fr')->getContent();
        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $m);
        $person = collect(json_decode($m[1], true)['@graph'])->firstWhere('@type', 'Person');
        $this->assertSame(['@type' => 'PostalAddress', 'addressLocality' => 'Cotonou', 'addressCountry' => 'Bénin'], $person['address']);
        $this->assertSame(['DOUBOVO', 'MEGATECH SARL'], array_column($person['worksFor'], 'name'));
        $this->assertEmpty(array_filter($person['sameAs'], fn ($u) => str_contains($u, 'wa.me')));
    }
}
