<?php

namespace App\Models;

use App\Enums\MaxBot\BotButton;
use App\Enums\UserEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'payload',
    ];

    protected $casts = [
        'event_type' => UserEventType::class,
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventTypeStrAttribute(): string
    {
        return $this->event_type->toString();
    }

    public function getCreatedAtStrAttribute(): string
    {
        return $this->created_at->format('d.m.Y');
    }

    public function getPayloadStrAttribute(): ?string
    {
        if (empty($this->payload)) {
            return '';
        }

        if (isset($this->payload['button'])) {
            $button = BotButton::tryFrom($this->payload['button']);
            return $button ? $button->label() : $this->payload['button'];
        }

        if (is_bool($this->payload)) {
            return $this->payload ? 'Успех' : 'Отказ';
        }

        return json_encode($this->payload, JSON_UNESCAPED_UNICODE);
    }

    public function getUserStrAttribute(): string
    {
        return $this->user->getNamePhoneStr();
    }
}
