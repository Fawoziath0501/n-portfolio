<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\HomeSection;
use App\Models\Media;
use App\Models\Message;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SkillGroup;
use App\Models\Subscriber;
use App\Models\Testimonial;
use App\Models\UiLabel;
use Illuminate\Support\Facades\DB;

/**
 * Assemble les données de la base dans la forme attendue par les applications Vue.
 */
class Portfolio
{
    /** Collections éditables depuis l'administration (clé front => modèle). */
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

    /** Éléments pouvant passer par la corbeille (clé => [modèle, libellé]). */
    public const TRASHABLE = [
        'projects' => [Project::class, 'Projet'],
        'posts' => [Post::class, 'Article'],
        'experiences' => [Experience::class, 'Expérience'],
        'education' => [Education::class, 'Formation'],
        'services' => [Service::class, 'Service'],
        'certifications' => [Certification::class, 'Certification'],
        'testimonials' => [Testimonial::class, 'Témoignage'],
        'skillGroups' => [SkillGroup::class, 'Groupe de compétences'],
        'messages' => [Message::class, 'Message'],
        'subscribers' => [Subscriber::class, 'Inscrit newsletter'],
        'media' => [Media::class, 'Fichier'],
    ];

    /** Durée de conservation dans la corbeille avant purge automatique. */
    public const TRASH_DAYS = 30;

    /** Réglages techniques stockés en clé / valeur. */
    public const SETTINGS = ['seo', 'settings'];

    public static function public(): array
    {
        $data = [];
        foreach (self::COLLECTIONS as $key => $model) {
            $q = $model::query()->ordered();
            $q = $key === 'skillGroups' ? $q->where('visible', true) : $q->published();
            $data[$key] = $q->get()->map->toFront()->values()->all();
        }
        $data['profile'] = self::profile(publicOnly: true);
        $data['home'] = self::home();
        $data['labels'] = UiLabel::dictionary();
        $data['seo'] = Setting::get('seo');
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
        $data['profile'] = self::profile();
        $data['home'] = self::home();
        foreach (self::SETTINGS as $key) {
            $data[$key] = Setting::get($key);
        }
        $data['messages'] = Message::latest()->get()->map->toFront()->all();
        $data['subscribers'] = Subscriber::latest()->get()->map->toFront()->all();
        $data['media'] = Media::latest()->get()->map->toFront()->all();
        $data['activity'] = Activity::latest('id')->limit(30)->get()
            ->map(fn ($a) => ['ts' => $a->created_at->getTimestampMs(), 'msg' => $a->msg])->all();
        $data['labels'] = UiLabel::forAdmin();
        $data['trashCount'] = self::trashCount();

        return $data;
    }

    public static function profile(bool $publicOnly = false): array
    {
        $profile = Profile::current()->toFront();
        if ($publicOnly) {
            $profile['socials'] = array_values(array_filter($profile['socials'], fn ($s) => $s['visible'] && $s['url']));
        }

        return $profile;
    }

    public static function home(): array
    {
        $p = Profile::query()->first(['cta_primary', 'cta_secondary']);

        return [
            'ctaPrimary' => $p?->cta_primary ?? [],
            'ctaSecondary' => $p?->cta_secondary ?? [],
            'sections' => HomeSection::orderBy('position')->get()->map->toFront()->all(),
        ];
    }

    /** Enregistre les CTA et l'ordre / l'affichage des sections de l'accueil. */
    public static function saveHome(array $home): void
    {
        DB::transaction(function () use ($home) {
            Profile::query()->first()?->update(array_filter([
                'cta_primary' => $home['ctaPrimary'] ?? null,
                'cta_secondary' => $home['ctaSecondary'] ?? null,
            ]));
            foreach (array_values($home['sections'] ?? []) as $i => $s) {
                HomeSection::updateOrCreate(['type' => $s['type']], ['enabled' => (bool) $s['enabled'], 'position' => $i]);
            }
        });
    }

    public static function trashCount(): int
    {
        return collect(self::TRASHABLE)->sum(fn ($t) => $t[0]::onlyTrashed()->count());
    }

    /** Traduction d'un champ { fr, en } (ou chaîne simple). */
    public static function tx(mixed $v, string $lang = 'fr'): string
    {
        if (is_array($v)) {
            return (string) (($v[$lang] ?? '') ?: ($v['fr'] ?? '') ?: ($v['en'] ?? ''));
        }

        return (string) ($v ?? '');
    }
}
