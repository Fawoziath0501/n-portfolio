<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['type', 'status', 'service', 'name', 'email', 'phone', 'subject', 'body', 'lang'];

    public function toFront(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'service' => $this->service,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'body' => $this->body,
            'lang' => $this->lang,
            'date' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
