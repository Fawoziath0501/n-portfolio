<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\LegalPage;
use App\Models\Profile;
use App\Models\Project;
use App\Support\RichText;
use Tests\TestCase;

class RichTextLegalTest extends TestCase
{
    public function test_rich_fields_are_sanitized_on_save(): void
    {
        $project = Project::first();

        $this->admin()->putJson("/api/admin/projects/{$project->id}", ['item' => ['context' => [
            'fr' => '<h2 style="color:red">Titre</h2><p onclick="x()">Texte <strong>gras</strong> <a href="javascript:alert(1)">piège</a> <a href="https://ok.dev">lien</a></p><script>alert(1)</script>',
            'en' => '<p>Text</p><img src=x onerror=alert(1)>',
        ]]])->assertOk();

        $context = $project->fresh()->context;
        $this->assertSame('<h2>Titre</h2><p>Texte <strong>gras</strong> <a rel="noopener noreferrer">piège</a> <a href="https://ok.dev" rel="noopener noreferrer">lien</a></p>', $context['fr']);
        $this->assertSame('<p>Text</p><img src="x" />', $context['en'], 'image gardée, attribut onerror retiré');
    }

    public function test_plain_text_becomes_paragraphs_and_plain_fields_stay_plain(): void
    {
        $exp = Experience::first();

        $this->admin()->putJson("/api/admin/experiences/{$exp->id}", ['item' => ['description' => ['fr' => "Ligne & une\nLigne <deux>", 'en' => '<p></p>']]])->assertOk();

        $this->assertSame(['fr' => '<p>Ligne &amp; une</p><p>Ligne &lt;deux&gt;</p>', 'en' => ''], $exp->fresh()->description);
        $this->assertSame('', RichText::clean('<p> </p>'));
    }

    public function test_profile_bio_is_rich(): void
    {
        $this->admin();
        $profile = $this->getJson('/api/admin/data')->json('profile');
        $profile['bio'] = ['fr' => '<p>Bio <em>riche</em></p><iframe src="https://x"></iframe>', 'en' => '<p>Rich bio</p>'];

        $this->putJson('/api/admin/documents/profile', ['value' => $profile])->assertOk();

        $this->assertSame('<p>Bio <em>riche</em></p>', Profile::current()->bio['fr']);
    }

    public function test_default_legal_pages(): void
    {
        $pages = LegalPage::ordered()->get()->keyBy('key');

        $this->assertSame(['legal', 'privacy', 'terms', 'cookies'], $pages->keys()->all());
        $this->assertFalse($pages['legal']->published, 'Mentions légales en brouillon tant que l’hébergeur n’est pas renseigné');
        $public = array_column($this->getJson('/api/site')->json('legalPages'), 'key');
        $this->assertSame(['privacy', 'terms', 'cookies'], $public);
    }

    public function test_legal_page_is_editable_and_sanitized(): void
    {
        $page = LegalPage::where('key', 'privacy')->first();
        $this->travelTo(now()->addYear());

        $res = $this->admin()->putJson("/api/admin/legalPages/{$page->id}", ['item' => [
            'key' => 'pirate', 'title' => ['fr' => 'Confidentialité', 'en' => 'Privacy'],
            'body' => ['fr' => '<p>Nouveau texte</p><script>x</script>', 'en' => '<p>New text</p>'],
        ]])->assertOk();

        $this->assertSame('<p>Nouveau texte</p>', $page->fresh()->body['fr']);
        $this->assertSame('privacy', $page->fresh()->key, 'la clé système ne se modifie pas');
        $this->assertSame(now()->toDateString(), $res->json('updatedAt'));
    }

    public function test_legal_slugs_are_validated(): void
    {
        $page = LegalPage::where('key', 'terms')->first();
        $this->admin();

        $this->putJson("/api/admin/legalPages/{$page->id}", ['item' => ['slugFr' => 'contact']])
            ->assertUnprocessable()->assertJsonValidationErrors('slugFr');
        $this->putJson("/api/admin/legalPages/{$page->id}", ['item' => ['slugEn' => 'privacy']])
            ->assertUnprocessable()->assertJsonValidationErrors('slugEn');
        $this->putJson("/api/admin/legalPages/{$page->id}", ['item' => ['slugFr' => 'Pas Bon']])
            ->assertUnprocessable()->assertJsonValidationErrors('slugFr');
        $this->putJson("/api/admin/legalPages/{$page->id}", ['item' => ['slugFr' => 'cgu', 'slugEn' => 'terms']])->assertOk();
        $this->get('/fr/cgu')->assertSee('<title>Conditions générales d', false);
    }

    public function test_legal_pages_have_their_own_title_and_are_in_sitemap(): void
    {
        $this->get('/fr/confidentialite')->assertOk()->assertSee('<title>Politique de confidentialité | ', false);
        $this->get('/en/privacy')->assertOk()->assertSee('<title>Privacy policy | ', false);
        $this->get('/en/cookie-policy')->assertOk()->assertSee('<title>Cookie policy | ', false);
        $this->get('/sitemap.xml')
            ->assertSee('/fr/confidentialite</loc>', false)->assertSee('/en/terms-of-use</loc>', false)
            ->assertDontSee('mentions-legales');
    }

    public function test_backup_import_keeps_legal_page_keys(): void
    {
        $this->admin();
        $backup = $this->getJson('/api/admin/export')->json();

        $this->postJson('/api/admin/import', ['data' => $backup])->assertNoContent();

        $this->assertSame(['legal', 'privacy', 'terms', 'cookies'], LegalPage::ordered()->pluck('key')->all());
        $this->assertSame(4, LegalPage::onlyTrashed()->count());
    }

    public function test_privacy_labels_exist_and_admin_link_is_gone(): void
    {
        $labels = $this->getJson('/api/site')->json('labels');

        $this->assertArrayHasKey('privacyLink', $labels);
        $this->assertArrayHasKey('formPrivacy', $labels);
        $this->assertArrayNotHasKey('adminLink', $labels);
    }
}
