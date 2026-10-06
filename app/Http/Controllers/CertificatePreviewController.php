<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Support\Facades\Storage;

/**
 * Aperçu public d'un certificat : image réduite (1000 px de large au plus), en JPEG, couverte d'un filigrane
 * répété « nom · Copie de consultation ». Le fichier original n'est jamais envoyé au public.
 * L'aperçu est calculé une fois puis gardé en cache (storage/app/private/previews).
 */
class CertificatePreviewController extends Controller
{
    private const MAX_WIDTH = 1000;

    public function show(Certification $certification)
    {
        $media = $certification->image;
        abort_unless($certification->published && $media && $media->path && Storage::disk('public')->exists($media->path), 404);

        $owner = trim(\App\Models\Profile::query()->value('first_name').' '.\App\Models\Profile::query()->value('last_name'));
        $cache = 'previews/cert-'.$certification->id.'-'.md5($media->path.$media->updated_at.$owner).'.jpg';
        if (! Storage::disk('local')->exists($cache)) {
            Storage::disk('local')->put($cache, $this->render(Storage::disk('public')->get($media->path), $owner));
        }

        return response(Storage::disk('local')->get($cache), 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="apercu-certificat.jpg"',
            'Cache-Control' => 'public, max-age=86400',
            'X-Robots-Tag' => 'noindex, noimageindex',
        ]);
    }

    private function render(string $bytes, string $owner): string
    {
        $src = @imagecreatefromstring($bytes);
        abort_unless($src, 404);
        [$w, $h] = [imagesx($src), imagesy($src)];
        $nw = min($w, self::MAX_WIDTH);
        $nh = (int) round($h * $nw / $w);
        $img = imagecreatetruecolor($nw, $nh);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagecopyresampled($img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        // Filigrane : texte oblique répété sur toute l'image, gris semi-transparent.
        $text = ($owner !== '' ? $owner.' · ' : '').'Copie de consultation';
        $color = imagecolorallocatealpha($img, 40, 50, 80, 88);
        $font = $this->font();
        $size = max(14, (int) round($nw / 38));
        if ($font) {
            $box = imagettfbbox($size, 30, $font, $text);
            $stepX = (int) (abs($box[2] - $box[0]) + $size * 3);
            $stepY = (int) ($size * 6);
            for ($y = -$nh; $y < $nh * 2; $y += $stepY) {
                for ($x = -$nw; $x < $nw * 2; $x += $stepX) {
                    imagettftext($img, $size, 30, $x + (($y / $stepY) % 2) * (int) ($stepX / 2), $y, $color, $font, $text);
                }
            }
        } else {
            for ($y = 10; $y < $nh; $y += 60) {
                for ($x = ($y / 60) % 2 * 120; $x < $nw; $x += 260) {
                    imagestring($img, 5, $x, $y, $text, $color);
                }
            }
        }

        ob_start();
        imagejpeg($img, null, 78);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    /** Police du filigrane : celle du projet, sinon une police système courante. */
    private function font(): ?string
    {
        foreach ([resource_path('fonts/HankenGrotesk-Bold.ttf'), '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf', 'C:/Windows/Fonts/arialbd.ttf'] as $f) {
            if (is_file($f)) {
                return $f;
            }
        }

        return null;
    }
}
