<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Événements du suivi intégré, agrégés côté navigateur par le tableau de bord. */
class StatsController extends Controller
{
    /** Événements d'un intervalle : ?from=AAAA-MM-JJ&to=AAAA-MM-JJ (ou ?days=N jusqu'à aujourd'hui), 800 jours au plus. */
    public function events(Request $request)
    {
        $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfDay()->subDays(min(400, max(1, (int) $request->query('days', 60))) - 1);
        $from = $from->max($to->copy()->startOfDay()->subDays(799));

        $events = Event::whereBetween('created_at', [$from, $to])
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
