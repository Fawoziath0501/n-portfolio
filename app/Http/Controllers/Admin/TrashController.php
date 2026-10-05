<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Corbeille : liste, restauration, suppression définitive. */
class TrashController extends Controller
{
    public function index()
    {
        $items = collect(Portfolio::TRASHABLE)->flatMap(fn ($def, $type) => $def[0]::onlyTrashed()->latest('deleted_at')->get()
            ->map(fn ($m) => [
                'type' => $type,
                'typeLabel' => $def[1],
                'id' => $m->id,
                'label' => $m->trashLabel(),
                'meta' => $m->trashMeta(),
                'deletedAt' => $m->deleted_at->toIso8601String(),
                'purgeAt' => $m->deleted_at->copy()->addDays(Portfolio::TRASH_DAYS)->toIso8601String(),
            ]))
            ->sortByDesc('deletedAt')->values();

        return response()->json(['items' => $items, 'days' => Portfolio::TRASH_DAYS]);
    }

    public function restore(Request $request, string $type, int $id)
    {
        $item = $this->model($type)::onlyTrashed()->findOrFail($id);
        $item->restore();
        Activity::log($this->def($type)[1].' restauré : '.$item->trashLabel());

        return response()->json(['ok' => true]);
    }

    public function destroy(string $type, int $id)
    {
        $item = $this->model($type)::onlyTrashed()->findOrFail($id);
        $label = $item->trashLabel();
        $item->forceDelete();
        Activity::log($this->def($type)[1].' supprimé définitivement : '.$label);

        return response()->noContent();
    }

    public function empty()
    {
        $n = 0;
        DB::transaction(function () use (&$n) {
            foreach (Portfolio::TRASHABLE as [$model]) {
                // Un par un pour déclencher les événements (fichiers de la médiathèque effacés du disque).
                $model::onlyTrashed()->get()->each(function ($m) use (&$n) {
                    $m->forceDelete();
                    $n++;
                });
            }
        });
        Activity::log('Corbeille vidée ('.$n.' élément(s))');

        return response()->noContent();
    }

    private function def(string $type): array
    {
        abort_unless(isset(Portfolio::TRASHABLE[$type]), 404);

        return Portfolio::TRASHABLE[$type];
    }

    private function model(string $type): string
    {
        return $this->def($type)[0];
    }
}
