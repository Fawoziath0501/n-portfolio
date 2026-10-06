<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\UiLabel;
use App\Support\Backups;
use App\Support\Menus;
use App\Support\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Export / import JSON de tout le contenu éditorial (relations comprises). */
class BackupController extends Controller
{
    /** Colonnes uniques par collection (clé front => colonne), corbeille comprise. */
    private const UNIQUE = [
        'projects' => ['slug' => 'slug'],
        'posts' => ['slug' => 'slug'],
        'legalPages' => ['slugFr' => 'slug_fr', 'slugEn' => 'slug_en', 'key' => 'key'],
    ];

    public function export()
    {
        $data = Portfolio::admin();
        unset($data['activity'], $data['trashCount']);

        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Remplace le contenu ; les éléments actuels passent par la corbeille (restaurables). */
    public function import(Request $request)
    {
        $request->validate(['data' => 'required|array', 'data.profile' => 'required|array', 'data.projects' => 'required|array']);
        $data = $request->input('data');

        DB::transaction(function () use ($data) {
            Profile::current()->saveFromFront($data['profile']);
            if (isset($data['home']) && is_array($data['home'])) {
                Portfolio::saveHome($data['home']);
            }
            foreach ($data['labels'] ?? [] as $l) {
                if (isset($l['key'])) {
                    UiLabel::where('key', $l['key'])->update(['fr' => (string) ($l['fr'] ?? ''), 'en' => (string) ($l['en'] ?? '')]);
                }
            }
            foreach (Portfolio::SETTINGS as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    Setting::put($key, $key === 'menus' ? Menus::clean($data[$key]) : $data[$key]);
                }
            }
            foreach (Portfolio::COLLECTIONS as $key => $model) {
                if (! isset($data[$key]) || ! is_array($data[$key])) {
                    continue;
                }
                $model::query()->get()->each->delete();
                foreach (array_values($data[$key]) as $i => $item) {
                    unset($item['id']);
                    // Libère les adresses (et la clé de page système) des éléments en corbeille en les renommant :
                    // ils restent restaurables.
                    foreach (self::UNIQUE[$key] ?? [] as $field => $column) {
                        $value = $item[$field] ?? null;
                        if ($value === null || $value === '') {
                            continue;
                        }
                        $model::onlyTrashed()->where($column, $value)->get()->each(fn ($old) => $old->forceFill([
                            $column => $column === 'key' ? null : $old->{$column}.'-'.$old->id.'-ancien',
                        ])->saveQuietly());
                    }
                    $record = new $model;
                    if ($key === 'legalPages') {
                        $record->forceFill(['key' => $item['key'] ?? null]);
                    }
                    if ($key === 'posts') {
                        $record->forceFill(['views' => max(0, (int) ($item['views'] ?? 0))]);
                    }
                    $record->saveFromFront($item + ['position' => $i]);
                }
            }
        });
        Activity::log('Sauvegarde restaurée');

        return response()->noContent();
    }

    /** Sauvegardes complètes (base + images) conservées sur le serveur. */
    public function archives()
    {
        return response()->json(Backups::list());
    }

    public function createArchive()
    {
        $file = Backups::create();
        Activity::log('Sauvegarde complète créée ('.basename($file).')');

        return response()->json(Backups::list());
    }

    public function downloadArchive(string $name)
    {
        $path = Backups::path($name);
        abort_unless($path, 404);

        return response()->download($path);
    }
}
