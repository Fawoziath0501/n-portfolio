<?php

namespace Tests\Feature;

use App\Models\Event;
use Tests\TestCase;

class StatsEventsTest extends TestCase
{
    private function event(int $daysAgo, string $path = '/fr'): void
    {
        Event::create(['type' => 'pv', 'path' => $path, 'device' => 'mobile', 'sid' => 's'.$daysAgo])
            ->forceFill(['created_at' => now()->subDays($daysAgo)->setTime(12, 0)])->saveQuietly();
    }

    public function test_events_can_be_loaded_for_a_date_range(): void
    {
        foreach ([1, 5, 15, 40, 200] as $d) {
            $this->event($d);
        }
        $this->admin();

        $range = fn ($from, $to) => $this->getJson('/api/admin/events?from='.now()->subDays($from)->toDateString().'&to='.now()->subDays($to)->toDateString())
            ->assertOk()->json('events');

        $this->assertCount(2, $range(20, 2));     // 5 et 15 jours
        $this->assertCount(1, $range(45, 30));    // 40 jours
        $this->assertCount(5, $this->getJson('/api/admin/events?days=400')->json('events'));
        $this->assertSame(5, $this->getJson('/api/admin/events?days=1')->json('total'));
    }

    public function test_invalid_ranges_are_rejected(): void
    {
        $this->admin()->getJson('/api/admin/events?from=2026-10-10&to=2026-10-01')->assertUnprocessable();
        $this->getJson('/api/admin/events?from=hier')->assertUnprocessable();
    }

    public function test_events_require_authentication(): void
    {
        $this->getJson('/api/admin/events')->assertUnauthorized();
    }
}
