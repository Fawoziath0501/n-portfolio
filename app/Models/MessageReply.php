<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Réponse à un message ou à une demande de service, envoyée par e-mail ou ouverte dans WhatsApp. */
class MessageReply extends Model
{
    protected $fillable = ['message_id', 'channel', 'subject', 'body', 'user_id'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function toFront(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'body' => $this->body,
            'at' => $this->created_at?->toIso8601String(),
        ];
    }
}
