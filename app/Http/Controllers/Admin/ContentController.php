<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Setting;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD générique des collections (projets, articles, expériences…) et des
 * documents uniques (profil, accueil, SEO, paramètres).
 */
class ContentController extends Controller
{
    public function index()
    {
        return response()->json(Portfolio::admin());
    }

    /** Création ; « order » (liste d'ids, 0 = nouvel élément) fixe sa place. */
    public function store(Request $request, string $collection)
    {
        $model = $this->model($collection);
        $item = (new $model)->fillFromFront($this->payload($request));
        $item->position = -1;
        $item->save();

        $order = $request->input('order');
        if (is_array($order)) {
            $order = array_map(fn ($id) => (int) $id === 0 ? $item->id : (int) $id, $order);
        }
        $this->renumber($model, $order);
        $this->log($request);

        return response()->json($item->fresh()->toFront(), 201);
    }

    public function update(Request $request, string $collection, int $id)
    {
        $item = $this->model($collection)::findOrFail($id);
        $item->fillFromFront($this->payload($request))->save();
        $this->log($request);

        return response()->json($item->fresh()->toFront());
    }

    public function destroy(Request $request, string $collection, int $id)
    {
        $this->model($collection)::findOrFail($id)->delete();
        $this->log($request);

        return response()->noContent();
    }

    /** Réordonne une collection : { ids: [3, 1, 2] }. */
    public function reorder(Request $request, string $collection)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $this->renumber($this->model($collection), $request->input('ids'));
        $this->log($request);

        return response()->noContent();
    }

    public function document(Request $request, string $key)
    {
        abort_unless(in_array($key, Portfolio::DOCUMENTS, true), 404);
        $request->validate(['value' => 'required|array']);
        Setting::put($key, $request->input('value'));
        $this->log($request);

        return response()->noContent();
    }

    private function renumber(string $model, ?array $ids): void
    {
        $ids ??= $model::query()->ordered()->pluck('id')->all();
        DB::transaction(function () use ($model, $ids) {
            foreach (array_values($ids) as $i => $id) {
                $model::whereKey($id)->update(['position' => $i]);
            }
        });
    }

    /** @return class-string<Model> */
    private function model(string $collection): string
    {
        abort_unless(isset(Portfolio::COLLECTIONS[$collection]), 404);

        return Portfolio::COLLECTIONS[$collection];
    }

    private function payload(Request $request): array
    {
        return collect($request->input('item', []))->except(['id', 'position', 'createdAt', 'updatedAt'])->all();
    }

    private function log(Request $request): void
    {
        if ($msg = $request->input('activity')) {
            Activity::log((string) $msg);
        }
    }
}
