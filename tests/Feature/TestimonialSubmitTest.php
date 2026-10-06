<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestimonialSubmitTest extends TestCase
{
    use RefreshDatabase;

    private array $data = ['name' => 'Kossi Agbo', 'email' => 'kossi@example.com', 'role' => 'Directeur', 'company' => 'ACME', 'quote' => 'Travail sérieux, à l’écoute et livré dans les délais. Je recommande vivement.', 'consent' => '1'];

    public function test_pages_are_available_and_not_indexed(): void
    {
        $this->get('/fr/temoignage')->assertOk()->assertSee('Laisser un témoignage')->assertSee('noindex, nofollow', false);
        $this->get('/en/testimonial')->assertOk()->assertSee('Leave a testimonial');
        $this->get('/fr/temoignage/merci')->assertOk()->assertSee('Merci beaucoup');
    }

    public function test_testimonial_is_saved_as_a_draft_with_consent_only(): void
    {
        Mail::fake();

        $this->post('/fr/temoignage', ['consent' => null] + $this->data)->assertSessionHasErrors('consent');
        $this->post('/fr/temoignage', ['quote' => 'Trop court'] + $this->data)->assertSessionHasErrors('quote');
        $this->assertSame(0, Testimonial::where('name', 'Kossi Agbo')->count());

        $this->post('/en/testimonial', $this->data)->assertRedirect('/en/testimonial/thanks');

        $t = Testimonial::where('name', 'Kossi Agbo')->sole();
        $this->assertFalse($t->published, 'jamais publié sans relecture');
        $this->assertSame($this->data['quote'], $t->quote['en']);
        $this->assertSame('Directeur', $t->role['en']);
    }

    public function test_bots_are_silently_ignored(): void
    {
        $this->post('/fr/temoignage', ['website' => 'http://spam.example'] + $this->data)->assertRedirect('/fr/temoignage/merci');
        $this->assertSame(0, Testimonial::where('name', 'Kossi Agbo')->count());
    }
}
