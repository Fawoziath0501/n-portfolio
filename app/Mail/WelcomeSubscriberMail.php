<?php

namespace App\Mail;

use App\Models\Subscriber;
use App\Support\MailIdentity;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/** Confirmation d'inscription à la newsletter, dans la langue du visiteur. */
class WelcomeSubscriberMail extends Mailable
{
    public array $me;

    public ?string $unsubscribe = null;

    public function __construct(public string $lang = 'fr', ?Subscriber $subscriber = null)
    {
        $this->unsubscribe = $subscriber ? PostNewsletterMail::unsubscribeUrl($subscriber) : null;
        $this->lang = $lang === 'en' ? 'en' : 'fr';
        $this->me = MailIdentity::for($this->lang);
        $this->locale($this->lang);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->lang === 'en' ? 'Welcome to my newsletter' : 'Bienvenue dans ma newsletter').' · '.$this->me['shortName'],
            replyTo: $this->me['email'] ? [new Address($this->me['email'], $this->me['shortName'])] : [],
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribe ? ['List-Unsubscribe' => '<'.$this->unsubscribe.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'] : []);
    }

    public function content(): Content
    {
        return new Content(html: 'mail.welcome', text: 'mail.welcome-text');
    }
}
