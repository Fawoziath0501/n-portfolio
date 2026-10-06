<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Réponse envoyée depuis l'administration, avec le message d'origine cité en dessous. */
class ReplyMail extends Mailable
{
    public function __construct(public Message $original, public string $replySubject, public string $replyBody) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->replySubject);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.reply');
    }
}
