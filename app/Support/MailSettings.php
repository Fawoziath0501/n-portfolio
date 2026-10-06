<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * Serveur d'envoi des e-mails (SMTP) réglé dans l'administration : il remplace la configuration du .env
 * pour les notifications et les réponses aux messages. Le mot de passe est chiffré en base (APP_KEY)
 * et n'est jamais renvoyé au navigateur.
 */
class MailSettings
{
    private const KEY = 'mail';

    /** Réglages bruts (mot de passe chiffré). */
    public static function get(): array
    {
        return Setting::get(self::KEY) + ['enabled' => false, 'host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '', 'password' => '', 'fromAddress' => '', 'fromName' => ''];
    }

    /** Pour l'administration : tout sauf le mot de passe (seulement s'il est renseigné). */
    public static function forAdmin(): array
    {
        $s = self::get();
        $s['hasPassword'] = $s['password'] !== '';
        $s['ready'] = self::ready();
        unset($s['password']);

        return $s;
    }

    /** Enregistre ; un mot de passe vide conserve l'actuel. */
    public static function save(array $v): void
    {
        $old = self::get();
        Setting::put(self::KEY, [
            'enabled' => (bool) ($v['enabled'] ?? false),
            'host' => trim((string) ($v['host'] ?? '')),
            'port' => (int) ($v['port'] ?? 587),
            'encryption' => in_array($v['encryption'] ?? 'tls', ['tls', 'ssl', 'none'], true) ? $v['encryption'] : 'tls',
            'username' => trim((string) ($v['username'] ?? '')),
            'password' => ($v['password'] ?? '') !== '' ? Crypt::encryptString($v['password']) : $old['password'],
            'fromAddress' => trim((string) ($v['fromAddress'] ?? '')),
            'fromName' => trim((string) ($v['fromName'] ?? '')),
        ]);
    }

    /** Applique les réglages à la configuration de Laravel avant un envoi (sans effet s'ils sont désactivés). */
    public static function apply(): void
    {
        $s = self::get();
        if (! $s['enabled'] || $s['host'] === '') {
            return;
        }
        $password = '';
        try {
            $password = $s['password'] !== '' ? Crypt::decryptString($s['password']) : '';
        } catch (\Throwable) {
            // Clé d'application changée : mot de passe illisible, à ressaisir dans l'administration.
        }
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $s['host'],
            'mail.mailers.smtp.port' => $s['port'],
            'mail.mailers.smtp.scheme' => $s['encryption'] === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.username' => $s['username'] ?: null,
            'mail.mailers.smtp.password' => $password ?: null,
            'mail.from.address' => $s['fromAddress'] ?: config('mail.from.address'),
            'mail.from.name' => $s['fromName'] ?: config('mail.from.name'),
        ]);
        app('mail.manager')->forgetMailers();
    }

    /** Envoi réel possible (SMTP réglé, ou un serveur configuré dans le .env autre que « log » / « array »). */
    public static function ready(): bool
    {
        $s = self::get();

        return ($s['enabled'] && $s['host'] !== '') || ! in_array(config('mail.default'), ['log', 'array'], true);
    }
}
