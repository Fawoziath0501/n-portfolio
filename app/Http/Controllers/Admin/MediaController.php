<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Media;
use App\Support\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:20',
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,gif,pdf|max:8192',
        ]);

        $created = [];
        foreach ($request->file('files') as $file) {
            $isDoc = strtolower($file->getClientOriginalExtension()) === 'pdf';
            $path = $file->store('media', 'public');
            [$w, $h] = $isDoc ? [0, 0] : (@getimagesize($file->getRealPath()) ?: [0, 0]);
            $created[] = Media::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'kind' => $isDoc ? 'doc' : 'image',
                'width' => $w,
                'height' => $h,
                'size' => $file->getSize(),
            ])->toFront();
        }
        Activity::log(count($created).' fichier(s) importé(s)');

        return response()->json($created, 201);
    }

    public function addUrl(Request $request)
    {
        $request->validate(['url' => 'required|url|max:500']);
        $media = Media::fromUrl($request->input('url'));
        Activity::log('Fichier ajouté');

        return response()->json($media->toFront(), 201);
    }

    /** Fichier placé dans la corbeille ; il n'est effacé du disque qu'à la suppression définitive. */
    public function destroy(Media $media)
    {
        $media->delete();
        Activity::log('Fichier placé dans la corbeille');

        return response()->json(['trashCount' => Portfolio::trashCount()]);
    }
}
