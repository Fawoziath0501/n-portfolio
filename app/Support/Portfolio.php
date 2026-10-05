<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Media;
use App\Models\Message;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SkillGroup;
use App\Models\Subscriber;
use App\Models\Testimonial;

/**
 * Assemble les données du portfolio dans la forme attendue par le front Vue
 * (la même que le « PortfolioStore » des maquettes).
 */
class Portfolio
{
    /** Collections éditables depuis l'administration. */
    public const COLLECTIONS = [
        'projects' => Project::class,
        'posts' => Post::class,
        'experiences' => Experience::class,
        'education' => Education::class,
        'services' => Service::class,
        'certifications' => Certification::class,
        'testimonials' => Testimonial::class,
        'skillGroups' => SkillGroup::class,
    ];

    /** Documents uniques stockés dans la table settings. */
    public const DOCUMENTS = ['profile', 'home', 'seo', 'settings'];

    public static function public(): array
    {
        $data = [];
        foreach (self::COLLECTIONS as $key => $model) {
            $q = $model::query()->ordered();
            $q = $key === 'skillGroups' ? $q->where('visible', true) : $q->published();
            $data[$key] = $q->get()->map->toFront()->values()->all();
        }
        foreach (['profile', 'home', 'seo'] as $doc) {
            $data[$doc] = Setting::get($doc);
        }
        $settings = Setting::get('settings');
        $data['settings'] = [
            'siteName' => $settings['siteName'] ?? '',
            'maintenance' => (bool) ($settings['maintenance'] ?? false),
            'tracking' => ($settings['analytics']['provider'] ?? '') === 'local',
        ];

        return $data;
    }

    public static function admin(): array
    {
        $data = [];
        foreach (self::COLLECTIONS as $key => $model) {
            $data[$key] = $model::query()->ordered()->get()->map->toFront()->values()->all();
        }
        foreach (self::DOCUMENTS as $doc) {
            $data[$doc] = Setting::get($doc);
        }
        $data['messages'] = Message::latest()->get()->map->toFront()->all();
        $data['subscribers'] = Subscriber::latest()->get()->map->toFront()->all();
        $data['media'] = Media::latest()->get()->map->toFront()->all();
        $data['activity'] = Activity::latest('id')->limit(30)->get()
            ->map(fn ($a) => ['ts' => $a->created_at->getTimestampMs(), 'msg' => $a->msg])->all();

        return $data;
    }

    /** Traduction d'un champ {fr, en} (ou chaîne simple). */
    public static function tx(mixed $v, string $lang = 'fr'): string
    {
        if (is_array($v)) {
            return (string) ($v[$lang] ?? $v['fr'] ?? $v['en'] ?? '');
        }

        return (string) ($v ?? '');
    }
}
