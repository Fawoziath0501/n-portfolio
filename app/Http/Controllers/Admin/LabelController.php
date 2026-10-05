<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\UiLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Textes de l'interface publique. */
class LabelController extends Controller
{
    /** Met à jour un ou plusieurs textes : { items: [{ id, fr, en }] }. */
    public function update(Request $request)
    {
        $items = $request->validate([
            'items' => 'required|array|max:300',
            'items.*.id' => 'required|integer|exists:ui_labels,id',
            'items.*.fr' => 'present|nullable|string|max:2000',
            'items.*.en' => 'present|nullable|string|max:2000',
        ])['items'];

        DB::transaction(function () use ($items) {
            foreach ($items as $it) {
                UiLabel::whereKey($it['id'])->update(['fr' => (string) $it['fr'], 'en' => (string) $it['en']]);
            }
        });
        Activity::log($request->input('activity'));

        return response()->noContent();
    }
}
