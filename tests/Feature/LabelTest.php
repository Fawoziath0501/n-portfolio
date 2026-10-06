<?php

namespace Tests\Feature;

use App\Models\UiLabel;
use Tests\TestCase;

class LabelTest extends TestCase
{
    public function test_labels_are_updated_and_served_to_the_site(): void
    {
        $label = UiLabel::first();

        $this->admin()->putJson('/api/admin/labels', [
            'items' => [['id' => $label->id, 'fr' => 'Accueil modifié', 'en' => null]],
        ])->assertNoContent();

        $this->assertSame(['Accueil modifié', ''], [$label->fresh()->fr, $label->fresh()->en]);
        $this->assertSame('Accueil modifié', $this->getJson('/api/site')->json('labels')[$label->key]['fr']);
    }

    public function test_label_validation(): void
    {
        $this->admin()->putJson('/api/admin/labels', ['items' => [['id' => 999999, 'fr' => 'x', 'en' => 'y']]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.id');

        $this->putJson('/api/admin/labels', ['items' => [['id' => UiLabel::first()->id]]])
            ->assertUnprocessable()->assertJsonValidationErrors(['items.0.fr', 'items.0.en']);
    }
}
