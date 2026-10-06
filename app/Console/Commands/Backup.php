<?php

namespace App\Console\Commands;

use App\Mail\BackupMail;
use App\Models\Setting;
use App\Support\Backups;
use App\Support\MailSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Sauvegarde de la base et des images ; --mail l'envoie aussi par e-mail (pièce jointe si elle n'est pas trop lourde). */
class Backup extends Command
{
    protected $signature = 'portfolio:backup {--mail : envoyer la sauvegarde par e-mail}';

    protected $description = 'Sauvegarde la base de données et les images du site';

    public function handle(): int
    {
        $file = Backups::create();
        $this->info('Sauvegarde : '.basename($file).' ('.round(filesize($file) / 1024).' Ko)');

        if ($this->option('mail')) {
            $to = Setting::get('settings')['notifyEmail'] ?? config('portfolio.admin.email');
            MailSettings::apply();
            Mail::to($to)->send(new BackupMail($file));
            $this->info('Envoyée à '.$to);
        }

        return self::SUCCESS;
    }
}
