<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Collections (projets, articles, expériences…) et documents (profil, accueil, SEO, paramètres).
 * La suppression place l'élément dans la corbeille (soft delete).
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
        $data = $this->payload($request);
        $this->checkSlug($collection, $data);

        $item = (new $model)->saveFromFront($data + ['position' => 0]);
        $order = $request->input('order');
        if (is_array($order)) {
            $order = array_map(fn ($id) => (int) $id === 0 ? $item->id : (int) $id, $order);
        }
        $this->renumber($model, $order);
        Activity::log($request->input('activity'));

        return response()->json($item->fresh()->toFront(), 201);
    }

    public function update(Request $request, string $collection, int $id)
    {
        $item = $this->model($collection)::findOrFail($id);
        $data = $this->payload($request);
        $this->checkSlug($collection, $data, $id);
        $item->saveFromFront($data);
        Activity::log($request->input('activity'));

        return response()->json($item->fresh()->toFront());
    }

    /** Place l'élément dans la corbeille. */
    public function destroy(Request $request, string $collection, int $id)
    {
        $this->model($collection)::findOrFail($id)->delete();
        Activity::log($request->input('activity') ?: 'Élément placé dans la corbeille');

        return response()->json(['trashCount' => Portfolio::trashCount()]);
    }

    /** Réordonne une collection : { ids: [3, 1, 2] }. */
    public function reorder(Request $request, string $collection)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $this->renumber($this->model($collection), $request->input('ids'));
        Activity::log($request->input('activity'));

        return response()->noContent();
    }

    public function document(Request $request, string $key)
    {
        $value = $request->validate(['value' => 'required|array'])['value'];

        match ($key) {
            'profile' => $this->saveProfile($value),
            'home' => Portfolio::saveHome($value),
            'seo', 'settings' => Setting::put($key, $value),
            default => abort(404),
        };
        Activity::log($request->input('activity'));

        return response()->json($key === 'profile' ? Portfolio::profile() : ['ok' => true]);
    }

    private function saveProfile(array $value): void
    {
        validator($value, [
            'firstName' => 'required|string|max:80',
            'lastName' => 'required|string|max:80',
            'email' => 'required|email',
            'since' => 'nullable|digits:4',
        ])->validate();
        Profile::current()->saveFromFront($value);
    }

    /** Le slug d'un projet reste unique, corbeille comprise (sinon la restauration échouerait). */
    private function checkSlug(string $collection, array $data, ?int $ignoreId = null): void
    {
        if ($collection !== 'projects' || ! array_key_exists('slug', $data)) {
            return;
        }
        $slug = trim((string) $data['slug']);
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw ValidationException::withMessages(['slug' => 'Le slug ne doit contenir que des minuscules, chiffres et tirets.']);
        }
        $taken = Project::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->first();
        if ($taken) {
            throw ValidationException::withMessages(['slug' => $taken->trashed()
                ? 'Ce slug est utilisé par un projet dans la corbeille. Restaurez-le ou supprimez-le définitivement.'
                : 'Ce slug est déjà utilisé par un autre projet.']);
        }
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
        return collect($request->input('item', []))->except(['id', 'position', 'createdAt', 'updatedAt', 'deletedAt'])->all();
    }
}
