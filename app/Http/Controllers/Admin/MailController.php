<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReplyMail;
use App\Models\Activity;
use App\Models\Message;
use App\Models\MessageReply;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/** Envoi d'e-mails depuis l'administration : réglages SMTP, e-mail de test, réponses aux messages. */
class MailController extends Controller
{
    public function settings(Request $request)
    {
        $v = $request->validate([
            'enabled' => 'boolean',
            'host' => 'nullable|string|max:190',
            'port' => 'nullable|integer|between:1,65535',
            'encryption' => 'nullable|in:tls,ssl,none',
            'username' => 'nullable|string|max:190',
            'password' => 'nullable|string|max:190',
            'fromAddress' => 'nullable|email|max:190',
            'fromName' => 'nullable|string|max:120',
        ]);
        if (! empty($v['enabled']) && (empty($v['host']) || empty($v['fromAddress']))) {
            throw ValidationException::withMessages(['host' => 'Renseignez au moins le serveur et l’adresse d’expédition.']);
        }
        MailSettings::save($v);
        Activity::log('Réglages d’envoi des e-mails modifiés');

        return response()->json(MailSettings::forAdmin());
    }

    public function test(Request $request)
    {
        $to = $request->validate(['to' => 'required|email'])['to'];
        $this->send(fn () => Mail::raw(
            "Bonjour,\n\nCet e-mail de test confirme que l’envoi depuis l’administration du portfolio fonctionne.\n",
            fn ($m) => $m->to($to)->subject('Test d’envoi · portfolio')
        ));

        return response()->json(['ok' => true]);
    }

    /** Réponse par e-mail : envoyée au visiteur, puis gardée dans l'historique du message. */
    public function reply(Request $request, Message $message)
    {
        $v = $request->validate(['subject' => 'required|string|max:190', 'body' => 'required|string|max:10000']);
        $this->send(fn () => Mail::to($message->email, $message->name)->send(new ReplyMail($message, $v['subject'], $v['body'])));

        return $this->record($request, $message, 'email', $v['subject'], $v['body'], 'Réponse envoyée par e-mail à '.$message->name);
    }

    /** Réponse WhatsApp : le texte est ouvert dans WhatsApp par le navigateur ; on garde la trace ici. */
    public function whatsapp(Request $request, Message $message)
    {
        $body = $request->validate(['body' => 'required|string|max:4000'])['body'];

        return $this->record($request, $message, 'whatsapp', '', $body, 'Réponse WhatsApp préparée pour '.$message->name);
    }

    private function record(Request $request, Message $message, string $channel, string $subject, string $body, string $log)
    {
        $message->replies()->create(['channel' => $channel, 'subject' => $subject, 'body' => $body, 'user_id' => $request->user()?->id]);
        $message->update(['status' => 'replied']);
        Activity::log($log);

        return response()->json($message->fresh('replies')->toFront());
    }

    /** Envoi avec les réglages SMTP de l'administration ; une erreur de serveur devient un message lisible. */
    private function send(callable $fn): void
    {
        if (! MailSettings::ready()) {
            throw ValidationException::withMessages(['mail' => 'L’envoi d’e-mails n’est pas configuré : renseignez le serveur SMTP dans Paramètres.']);
        }
        MailSettings::apply();
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('Envoi d’e-mail impossible : '.$e->getMessage());
            throw ValidationException::withMessages(['mail' => 'Envoi impossible : '.mb_strimwidth($e->getMessage(), 0, 180, '…')]);
        }
    }
}
