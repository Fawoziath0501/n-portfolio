<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\Subscriber;
use App\Support\MailIdentity;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/** Un article du blog envoyé à un abonné, dans sa langue, avec désinscription en un clic. */
class PostNewsletterMail extends Mailable
{
    public array $me;

    public string $lang;

    public string $title;

    public string $excerpt;

    public string $link;

    public ?string $image;

    public string $unsubscribe;

    public function __construct(public Post $post, public Subscriber $subscriber)
    {
        $this->lang = $subscriber->lang === 'en' ? 'en' : 'fr';
        $this->me = MailIdentity::for($this->lang);
        $tx = fn ($v) => trim(strip_tags((string) (($v[$this->lang] ?? '') ?: ($v['fr'] ?? ''))));
        $this->title = $tx($post->title);
        $this->excerpt = $tx($post->excerpt);
        $this->link = rtrim((string) config('app.url'), '/').'/'.$this->lang.'/blog/'.$post->slug;
        $this->image = $post->shareImageUrl($this->lang);
        $this->unsubscribe = self::unsubscribeUrl($subscriber);
        $this->locale($this->lang);
    }

    /** Lien signé de désinscription (sans date d'expiration). */
    public static function unsubscribeUrl(Subscriber $subscriber): string
    {
        // Signature relative : valable quel que soit le protocole ou le domaine vu par le serveur.
        return rtrim((string) config('app.url'), '/').URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id], absolute: false);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
            replyTo: $this->me['email'] ? [new Address($this->me['email'], $this->me['shortName'])] : [],
        );
    }

    /** Bouton « Se désabonner » de Gmail / Outlook (désinscription en un clic). */
    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribe.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    public function content(): Content
    {
        return new Content(html: 'mail.post', text: 'mail.post-text');
    }
}
