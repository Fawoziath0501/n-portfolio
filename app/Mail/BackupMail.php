<?php

namespace App\Mail;

use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sauvegarde hebdomadaire envoyée à l'administratrice (jointe si elle pèse moins de 20 Mo). */
class BackupMail extends Mailable
{
    public const MAX_ATTACHMENT = 20 * 1024 * 1024;

    public bool $attached;

    public string $name;

    public string $size;

    public function __construct(public string $file)
    {
        $this->attached = filesize($file) <= self::MAX_ATTACHMENT;
        $this->name = basename($file);
        $this->size = round(filesize($file) / 1024 / 1024, 1).' Mo';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sauvegarde du portfolio · '.now()->locale('fr')->translatedFormat('j F Y'));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.backup');
    }

    public function attachments(): array
    {
        return $this->attached ? [Attachment::fromPath($this->file)->as($this->name)] : [];
    }
}
