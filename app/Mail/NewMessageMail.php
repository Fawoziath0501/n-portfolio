<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewMessageMail extends Mailable
{
    public function __construct(public Message $msg) {}

    public function envelope(): Envelope
    {
        $kind = $this->msg->type === 'service' ? 'Demande de service' : 'Nouveau message';

        return new Envelope(subject: $kind.' · '.$this->msg->name, replyTo: [$this->msg->email]);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.new-message');
    }
}
