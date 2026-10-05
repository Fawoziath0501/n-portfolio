<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

/** Événements du suivi intégré, agrégés côté navigateur par le tableau de bord. */
class StatsController extends Controller
{
    public function events(Request $request)
    {
        $days = min(400, max(1, (int) $request->query('days', 60)));
        $events = Event::where('created_at', '>=', now()->startOfDay()->subDays($days - 1))
            ->orderBy('id')
            ->get(['type', 'key', 'path', 'ref', 'device', 'sid', 'created_at'])
            ->map(fn ($e) => [
                't' => $e->type,
                'k' => $e->key,
                'path' => $e->path,
                'ref' => $e->ref,
                'dev' => $e->device,
                'sid' => $e->sid,
                'ts' => $e->created_at->getTimestampMs(),
            ]);

        return response()->json(['events' => $events, 'total' => Event::count()]);
    }

    public function clear()
    {
        Event::query()->delete();

        return response()->noContent();
    }
}
