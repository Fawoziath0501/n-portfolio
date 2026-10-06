<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Profile;
use Illuminate\Support\Facades\Storage;

/**
 * Image de partage d'un article sans couverture (1200 × 630, aux couleurs du site) : titre, étiquettes et adresse.
 * Utilisée pour les aperçus LinkedIn / WhatsApp / Facebook et la newsletter. Calculée une fois puis gardée en cache.
 */
class PostCoverController extends Controller
{
    private const W = 1200;

    private const H = 630;

    public function show(string $slug, string $lang)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $lang = $lang === 'en' ? 'en' : 'fr';
        $title = trim(strip_tags((string) (($post->title[$lang] ?? '') ?: ($post->title['fr'] ?? ''))));
        $tags = collect($post->tags)->map(fn ($t) => $t->name[$lang] ?? $t->name['fr'] ?? '')->filter()->take(3)->all();
        $p = Profile::query()->first();
        $owner = trim(($p?->first_name ?? '').' '.($p?->last_name ?? ''));
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        $cache = 'previews/post-'.$post->id.'-'.$lang.'-'.md5($title.implode('|', $tags).$owner.$host).'.png';
        if (! Storage::disk('local')->exists($cache)) {
            Storage::disk('local')->put($cache, $this->render($title, $tags, $owner, $host, $lang));
        }

        return response(Storage::disk('local')->get($cache), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function render(string $title, array $tags, string $owner, string $host, string $lang): string
    {
        $img = imagecreatetruecolor(self::W, self::H);
        $navy = imagecolorallocate($img, 11, 21, 48);
        $blue = imagecolorallocate($img, 36, 72, 200);
        $soft = imagecolorallocate($img, 143, 163, 232);
        $white = imagecolorallocate($img, 255, 255, 255);
        $grey = imagecolorallocate($img, 195, 203, 224);
        $line = imagecolorallocatealpha($img, 143, 163, 232, 112);
        imagefill($img, 0, 0, $navy);

        // Trame discrète et bandeau bleu en haut.
        for ($x = 0; $x < self::W; $x += 60) {
            imageline($img, $x, 0, $x, self::H, $line);
        }
        imagefilledrectangle($img, 0, 0, self::W, 10, $blue);
        $font = $this->font();
        $pad = 80;

        // En-tête : marque et rubrique.
        $this->text($img, 24, $pad, 112, $soft, $font, '[ FS ]  '.mb_strtoupper(($owner ?: 'Blog').' · Blog'));

        // Titre : la plus grande taille qui tient entre l’en-tête et les étiquettes (zone de 300 px de haut).
        foreach ([66, 60, 54, 48, 42, 38, 34] as $size) {
            $lines = $this->wrap($title, $font, $size, self::W - 2 * $pad);
            if (count($lines) * $size * 1.2 <= 300) {
                break;
            }
        }
        $y = 175 + $size;
        foreach (array_slice($lines, 0, 6) as $l) {
            $this->text($img, $size, $pad, $y, $white, $font, $l);
            $y += (int) round($size * 1.2);
        }

        // Étiquettes et adresse du site.
        if ($tags) {
            $this->text($img, 24, $pad, self::H - 112, $soft, $font, '#'.implode('   #', $tags));
        }
        imagefilledrectangle($img, $pad, self::H - 82, $pad + 14, self::H - 68, $blue);
        $this->text($img, 22, $pad + 28, self::H - 66, $grey, $font, $host.'/'.$lang.'/blog');

        ob_start();
        imagepng($img, null, 6);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    /** Découpe le titre en lignes qui tiennent dans la largeur donnée. */
    private function wrap(string $text, ?string $font, int $size, int $max): array
    {
        $lines = [];
        $cur = '';
        foreach (preg_split('/\s+/u', $text) as $word) {
            $try = $cur === '' ? $word : $cur.' '.$word;
            if ($cur !== '' && $this->width($try, $font, $size) > $max) {
                $lines[] = $cur;
                $cur = $word;
            } else {
                $cur = $try;
            }
        }

        return $cur === '' ? $lines : [...$lines, $cur];
    }

    private function width(string $text, ?string $font, int $size): int
    {
        if (! $font) {
            return strlen($text) * 9;
        }
        $box = imagettfbbox($size, 0, $font, $text);

        return abs($box[2] - $box[0]);
    }

    private function text($img, int $size, int $x, int $y, int $color, ?string $font, string $text): void
    {
        $font ? imagettftext($img, $size, 0, $x, $y, $color, $font, $text) : imagestring($img, 5, $x, $y - 14, $text, $color);
    }

    private function font(): ?string
    {
        foreach ([resource_path('fonts/HankenGrotesk-Bold.ttf'), '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 'C:/Windows/Fonts/arialbd.ttf'] as $f) {
            if (is_file($f)) {
                return $f;
            }
        }

        return null;
    }
}
