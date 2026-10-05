<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Profile;
use App\Models\Setting;
use App\Support\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Export / import JSON de tout le contenu éditorial (relations comprises). */
class BackupController extends Controller
{
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
            foreach (Portfolio::SETTINGS as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    Setting::put($key, $data[$key]);
                }
            }
            foreach (Portfolio::COLLECTIONS as $key => $model) {
                if (! isset($data[$key]) || ! is_array($data[$key])) {
                    continue;
                }
                $model::query()->get()->each->delete();
                foreach (array_values($data[$key]) as $i => $item) {
                    unset($item['id']);
                    if ($key === 'projects') {
                        // Libère le slug s'il appartient à un projet déjà en corbeille.
                        $model::onlyTrashed()->where('slug', $item['slug'] ?? '')->get()->each->forceDelete();
                    }
                    (new $model)->saveFromFront($item + ['position' => $i]);
                }
            }
        });
        Activity::log('Sauvegarde restaurée');

        return response()->noContent();
    }
}
