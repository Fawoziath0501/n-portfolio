<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Setting;
use App\Support\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Export / import JSON de tout le contenu éditorial. */
class BackupController extends Controller
{
    public function export()
    {
        $data = Portfolio::admin();
        unset($data['activity']);

        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function import(Request $request)
    {
        $request->validate(['data' => 'required|array', 'data.profile' => 'required|array', 'data.projects' => 'required|array']);
        $data = $request->input('data');

        DB::transaction(function () use ($data) {
            foreach (Portfolio::DOCUMENTS as $doc) {
                if (isset($data[$doc]) && is_array($data[$doc])) {
                    Setting::put($doc, $data[$doc]);
                }
            }
            foreach (Portfolio::COLLECTIONS as $key => $model) {
                if (! isset($data[$key]) || ! is_array($data[$key])) {
                    continue;
                }
                $model::query()->delete();
                foreach (array_values($data[$key]) as $i => $item) {
                    unset($item['id']);
                    (new $model)->fillFromFront($item + ['position' => $i])->save();
                }
            }
        });
        Activity::log('Sauvegarde restaurée');

        return response()->noContent();
    }
}
