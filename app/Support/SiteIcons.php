<?php

namespace App\Support;

use App\Models\Post;
use App\Models\ProfileValue;
use App\Models\Service;
use App\Models\Skill;
use App\Models\SkillGroup;

/**
 * Icônes Material Symbols utilisées par le site public. Google Fonts ne sert alors que ces glyphes
 * (quelques Ko au lieu de ~3 Mo pour la police complète). Les icônes choisies dans l'administration
 * (services, articles, compétences, méthode) sont lues en base à chaque page.
 */
class SiteIcons
{
    /** Icônes écrites dans les composants du site public (vérifié par SiteIconsTest). */
    public const STATIC = [
        'arrow_back', 'arrow_forward', 'arrow_upward', 'article', 'bolt', 'calendar_today', 'call', 'check', 'check_circle',
        'chevron_right', 'close', 'code', 'design_services', 'download', 'event_note', 'favorite', 'folder_open', 'gavel',
        'grid_view', 'image', 'insights', 'language', 'link', 'location_on', 'mail', 'mark_email_unread', 'open_in_new',
        'person', 'schedule', 'search', 'send', 'south', 'translate', 'verified', 'visibility', 'work',
    ];

    public static function names(): array
    {
        $dynamic = collect([Service::class, Post::class, Skill::class, SkillGroup::class, ProfileValue::class])
            ->flatMap(fn ($model) => $model::query()->whereNotNull('icon')->distinct()->pluck('icon'));

        return collect(self::STATIC)->merge($dynamic)
            ->filter(fn ($n) => is_string($n) && preg_match('/^[a-z0-9_]+$/', $n))
            ->unique()->sort()->values()->all(); // Google Fonts exige une liste triée
    }
}
