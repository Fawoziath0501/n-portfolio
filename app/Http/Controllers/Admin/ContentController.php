<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Profile;
use App\Models\Setting;
use App\Support\Menus;
use App\Support\Portfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'menus' => Setting::put('menus', Menus::clean($value)),
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
    /** Champs d'adresse (slug) par collection, uniques entre eux et réservés aux pages du site. */
    private const SLUG_FIELDS = ['projects' => ['slug'], 'posts' => ['slug'], 'legalPages' => ['slugFr', 'slugEn']];

    /** Segments déjà utilisés par le site : une page légale ne peut pas les prendre. */
    private const RESERVED = ['a-propos', 'about', 'projets', 'work', 'services', 'contact', 'blog', 'admin', 'api', 'storage'];

    private function checkSlug(string $collection, array $data, ?int $ignoreId = null): void
    {
        $model = Portfolio::COLLECTIONS[$collection];
        foreach (self::SLUG_FIELDS[$collection] ?? [] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $slug = trim((string) $data[$field]);
            if ($slug === '' && $collection === 'posts') {
                continue; // article : adresse générée depuis le titre
            }
            if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                throw ValidationException::withMessages([$field => 'L’adresse ne doit contenir que des minuscules, chiffres et tirets.']);
            }
            if ($collection === 'legalPages' && in_array($slug, self::RESERVED, true)) {
                throw ValidationException::withMessages([$field => 'Cette adresse est déjà utilisée par une page du site.']);
            }
            $columns = array_map(fn ($f) => Str::snake($f), self::SLUG_FIELDS[$collection]);
            $taken = $model::withTrashed()->where(fn ($q) => array_map(fn ($c) => $q->orWhere($c, $slug), $columns))
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->first();
            if ($taken) {
                throw ValidationException::withMessages([$field => $taken->trashed()
                    ? 'Cette adresse est utilisée par un élément dans la corbeille. Restaurez-le ou supprimez-le définitivement.'
                    : 'Cette adresse est déjà utilisée par un autre élément.']);
            }
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
