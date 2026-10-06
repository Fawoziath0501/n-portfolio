<?php

namespace App\Http\Controllers;

use App\Mail\PostNewsletterMail;
use App\Models\Activity;
use App\Models\Post;
use App\Models\Subscriber;
use App\Support\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/** Newsletter : envoi d'un article aux abonnés (administration) et désinscription en un clic (lien signé). */
class NewsletterController extends Controller
{
    /** Limite quotidienne d'envoi de Gmail (500) avec une marge pour les autres e-mails du site. */
    public const MAX_RECIPIENTS = 450;

    /** Lien de désinscription (GET depuis l'e-mail, POST depuis le bouton « Se désabonner » de Gmail). */
    public function unsubscribe(Request $request, int $subscriber)
    {
        $sub = Subscriber::withTrashed()->find($subscriber);
        $lang = $sub?->lang === 'en' ? 'en' : 'fr';
        if ($sub && ! $sub->trashed()) {
            $sub->delete();
            Activity::log('Désinscription de la newsletter : '.$sub->email);
        }

        return $request->isMethod('post')
            ? response()->noContent()
            : response()->view('newsletter-unsubscribed', ['lang' => $lang, 'site' => url('/'.$lang)]);
    }

    /** Envoie un article publié à tous les abonnés, chacun dans sa langue. */
    public function send(Request $request, Post $post)
    {
        abort_unless($post->published, 422, 'Publiez l’article avant de l’envoyer.');
        if ($post->newsletter_sent_at && ! $request->boolean('force')) {
            throw ValidationException::withMessages(['post' => 'Cet article a déjà été envoyé le '.$post->newsletter_sent_at->locale('fr')->translatedFormat('j F Y').'.']);
        }
        $subscribers = Subscriber::query()->orderBy('id')->get();
        if ($subscribers->isEmpty()) {
            throw ValidationException::withMessages(['post' => 'Aucun abonné pour le moment.']);
        }
        if ($subscribers->count() > self::MAX_RECIPIENTS) {
            throw ValidationException::withMessages(['post' => 'Plus de '.self::MAX_RECIPIENTS.' abonnés : Gmail limite les envois quotidiens. Un service d’envoi dédié (Brevo…) sera nécessaire.']);
        }

        MailSettings::apply();
        $sent = 0;
        $failed = 0;
        foreach ($subscribers as $sub) {
            try {
                Mail::to($sub->email)->send(new PostNewsletterMail($post, $sub));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Newsletter non envoyée à '.$sub->email.' : '.$e->getMessage());
            }
        }
        if ($sent) {
            $post->forceFill(['newsletter_sent_at' => now()])->saveQuietly();
        }
        Activity::log('Article « '.($post->title['fr'] ?? $post->slug).' » envoyé à '.$sent.' abonné(s)'.($failed ? ', '.$failed.' échec(s)' : ''));

        return response()->json(['sent' => $sent, 'failed' => $failed, 'at' => $post->newsletter_sent_at?->toIso8601String()]);
    }
}
