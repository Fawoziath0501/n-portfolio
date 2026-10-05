<?php

namespace App\Models;

use App\Models\Concerns\ContentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    use ContentModel;

    protected function frontHidden(): array
    {
        return ['created_at', 'updated_at', 'position', 'skill_group_id'];
    }

    protected $fillable = ['name', 'note', 'logo', 'icon', 'hero_position', 'position'];

    /** Logos connus : une compétence ajoutée avec ce nom reçoit le logo automatiquement. */
    public const LOGOS = [
        'laravel' => 'laravel', 'php' => 'php', 'vue.js' => 'vuejs', 'javascript' => 'javascript', 'html' => 'html5', 'css' => 'css3',
        'angular' => 'angularjs', 'python' => 'python', 'git' => 'git', 'github' => 'github', 'gitlab' => 'gitlab',
        'google search console' => 'google', 'google analytics' => 'google',
    ];

    protected function casts(): array
    {
        return ['name' => 'array', 'note' => 'array', 'hero_position' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SkillGroup::class, 'skill_group_id');
    }

    public static function attributesFromFront(array $s): array
    {
        $name = is_array($s['name'] ?? null) ? $s['name'] : ['fr' => (string) ($s['name'] ?? ''), 'en' => (string) ($s['name'] ?? '')];
        $key = strtolower(trim($name['fr'] ?: $name['en']));
        $logo = $s['logo'] ?? (isset(self::LOGOS[$key]) ? '/images/devicons/dv_'.self::LOGOS[$key].'.svg' : null);

        return [
            'name' => $name,
            'note' => $s['note'] ?? null,
            'logo' => $logo ?: null,
            'icon' => $logo ? null : ($s['icon'] ?? 'code'),
            'hero_position' => isset($s['heroPosition']) && $s['heroPosition'] !== '' ? (int) $s['heroPosition'] : null,
        ];
    }
}
