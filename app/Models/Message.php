<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use Trashable;

    protected $fillable = ['type', 'status', 'service_id', 'service', 'name', 'email', 'phone', 'subject', 'body', 'lang'];

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id')->withTrashed();
    }

    /** Réponses envoyées depuis l'administration, de la plus ancienne à la plus récente. */
    public function replies(): HasMany
    {
        return $this->hasMany(MessageReply::class)->orderBy('id');
    }

    public function trashLabel(): string
    {
        return $this->name.' · '.($this->subject ?: '(sans sujet)');
    }

    public function trashMeta(): string
    {
        return $this->email;
    }

    public function toFront(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'serviceId' => $this->service_id,
            'service' => $this->service,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'body' => $this->body,
            'lang' => $this->lang,
            'date' => $this->created_at?->format('Y-m-d'),
            'at' => $this->created_at?->toIso8601String(),
            'replies' => $this->replies->map->toFront()->all(),
        ];
    }
}
