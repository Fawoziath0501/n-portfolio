<?php

namespace App\Mail;

use App\Models\Message;
use App\Support\MailIdentity;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Accusé de réception envoyé au visiteur après un message ou une demande de service, dans sa langue.
 * Le texte du visiteur n'est jamais recopié : le formulaire ne peut pas servir à relayer du spam vers des tiers.
 */
class AckMail extends Mailable
{
    public array $me;

    public string $lang;

    public ?string $greetName;

    public ?string $serviceName;

    public string $reference;

    public string $receivedOn;

    public function __construct(public Message $msg)
    {
        $this->lang = $msg->lang === 'en' ? 'en' : 'fr';
        $this->me = MailIdentity::for($this->lang);
        $this->greetName = MailIdentity::safeName($msg->name);
        // Nom du service seulement s'il correspond à un service du site (jamais un texte libre).
        $this->serviceName = $msg->service_id ? ($msg->serviceItem?->title[$this->lang] ?? $msg->serviceItem?->title['fr'] ?? null) : null;
        // Référence lisible : DEM-0042 (demande) ou MSG-0042 (message).
        $this->reference = ($msg->type === 'service' ? 'DEM-' : 'MSG-').str_pad((string) $msg->id, 4, '0', STR_PAD_LEFT);
        $this->receivedOn = ($msg->created_at ?? now())->locale($this->lang)->translatedFormat('j F Y');
        $this->locale($this->lang);
    }

    public function envelope(): Envelope
    {
        $en = $this->lang === 'en';
        $subject = $this->msg->type === 'service'
            ? ($en ? 'Your request has been received' : 'Votre demande a bien été reçue')
            : ($en ? 'Your message has been received' : 'Votre message a bien été reçu');

        return new Envelope(
            subject: $subject.' · '.$this->me['shortName'],
            replyTo: $this->me['email'] ? [new Address($this->me['email'], $this->me['shortName'])] : [],
        );
    }

    public function content(): Content
    {
        return new Content(html: 'mail.ack', text: 'mail.ack-text');
    }

}
