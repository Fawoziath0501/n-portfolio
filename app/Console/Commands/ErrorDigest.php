<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\MailSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Récapitulatif quotidien des erreurs du serveur (dernières 24 h), envoyé seulement s'il y en a. */
class ErrorDigest extends Command
{
    protected $signature = 'portfolio:error-digest {--path= : dossier des journaux (par défaut storage/logs)}';

    protected $description = 'Envoie par e-mail le récapitulatif des erreurs des dernières 24 heures';

    public function handle(): int
    {
        $errors = self::recent($this->option('path') ?: null);
        if (! $errors) {
            $this->info('Aucune erreur ces dernières 24 h.');

            return self::SUCCESS;
        }
        $to = Setting::get('settings')['notifyEmail'] ?? config('portfolio.admin.email');
        $lines = collect($errors)->take(10)->map(fn ($e) => '• '.$e['at'].' · '.$e['message'])->implode("\n");
        $body = count($errors).' erreur(s) enregistrée(s) par le serveur ces dernières 24 heures.'."\n\n"
            .$lines."\n\n".(count($errors) > 10 ? '… et '.(count($errors) - 10).' autre(s).'."\n\n" : '')
            .'Détails complets : '.url('/'.config('portfolio.admin.path').'/logs');

        MailSettings::apply();
        Mail::raw($body, fn ($m) => $m->to($to)->subject('Portfolio · '.count($errors).' erreur(s) à vérifier'));
        $this->info(count($errors).' erreur(s) signalée(s) à '.$to);

        return self::SUCCESS;
    }

    /** Erreurs (error et plus grave) des dernières 24 h, les plus récentes d'abord, message sur une ligne. */
    public static function recent(?string $dir = null): array
    {
        $dir ??= storage_path('logs');
        $since = now()->subDay()->format('Y-m-d H:i:s');
        $out = [];
        foreach (glob(rtrim($dir, '/\\').'/*.log') ?: [] as $path) {
            if (filemtime($path) < now()->subDay()->getTimestamp()) {
                continue;
            }
            $h = fopen($path, 'rb');
            if (filesize($path) > 512 * 1024) {
                fseek($h, -512 * 1024, SEEK_END);
            }
            preg_match_all('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] \w+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY): ([^\n]*)/m', (string) stream_get_contents($h), $m, PREG_SET_ORDER);
            fclose($h);
            foreach ($m as [, $at, $msg]) {
                if (str_replace('T', ' ', $at) >= $since) {
                    $out[] = ['at' => str_replace('T', ' ', $at), 'message' => mb_strimwidth(trim($msg), 0, 220, '…')];
                }
            }
        }
        usort($out, fn ($a, $b) => strcmp($b['at'], $a['at']));

        return $out;
    }
}
