<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Support\Portfolio;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /** Page publique (SPA Vue) avec métadonnées SEO rendues côté serveur. */
    public function show(Request $request)
    {
        $segments = $request->segments();
        $lang = ($segments[0] ?? 'fr') === 'en' ? 'en' : 'fr';
        $seo = Setting::get('seo');
        $data = Portfolio::public();

        $title = Portfolio::tx($seo['siteTitle'] ?? '', $lang);
        $description = Portfolio::tx($seo['metaDescription'] ?? '', $lang);

        if (in_array($segments[1] ?? '', ['projets', 'work'], true) && isset($segments[2])) {
            $project = Project::published()->where('slug', $segments[2])->first();
            if ($project) {
                $title = Portfolio::tx($project->title, $lang).' | Fawoziath Salou';
                $description = Portfolio::tx($project->summary, $lang);
            }
        }

        return view('site', [
            'lang' => $lang,
            'title' => $title,
            'description' => $description,
            'seo' => $seo,
            'profile' => $data['profile'],
            'data' => $data,
        ]);
    }

    public function data()
    {
        return response()->json(Portfolio::public());
    }
}
