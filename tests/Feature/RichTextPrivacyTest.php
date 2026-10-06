<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Support\RichText;
use Tests\TestCase;

class RichTextPrivacyTest extends TestCase
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
        $this->assertSame('<p>Text</p>', $context['en']);
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

    public function test_privacy_policy_is_editable_and_sanitized(): void
    {
        $this->admin();
        $privacy = $this->getJson('/api/admin/data')->json('privacy');
        $this->assertStringContainsString('{email}', $privacy['body']['fr']);

        $this->travelTo(now()->setDate(2027, 1, 15));
        $this->putJson('/api/admin/documents/privacy', ['value' => [
            'title' => ['fr' => 'Confidentialité', 'en' => 'Privacy'],
            'body' => ['fr' => '<p>Nouveau texte</p><script>x</script>', 'en' => '<p>New text</p>'],
            'updatedAt' => '2020-01-01',
        ]])->assertOk();

        $saved = Setting::get('privacy');
        $this->assertSame('<p>Nouveau texte</p>', $saved['body']['fr']);
        $this->assertSame('2027-01-15', $saved['updatedAt']);
        $this->assertSame('<p>Nouveau texte</p>', $this->getJson('/api/site')->json('privacy.body.fr'));
    }

    public function test_privacy_page_has_its_own_title_and_is_in_sitemap(): void
    {
        $this->get('/fr/confidentialite')->assertOk()->assertSee('<title>Politique de confidentialité | ', false);
        $this->get('/en/privacy')->assertOk()->assertSee('<title>Privacy policy | ', false);
        $this->get('/sitemap.xml')->assertSee('/fr/confidentialite</loc>', false)->assertSee('/en/privacy</loc>', false);
    }

    public function test_privacy_labels_exist_and_admin_link_is_gone(): void
    {
        $labels = $this->getJson('/api/site')->json('labels');

        $this->assertArrayHasKey('privacyLink', $labels);
        $this->assertArrayHasKey('formPrivacy', $labels);
        $this->assertArrayNotHasKey('adminLink', $labels);
    }
}
