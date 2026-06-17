<?php

namespace App\Models;

use App\Enums\ReceiptResponseKey;
use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => ReceiptStatus::class,
        'source' => ReceiptSource::class,
        'responses' => 'array',
        'skus' => 'array',
    ];

    public function getPhoneUserAttribute(): string
    {
        return $this->user->phone ?? '-';
    }

    public function getResponsesStrAttribute(): string
    {
        return $this->responses ? json_encode($this->responses, JSON_UNESCAPED_UNICODE) : '-';
    }

    public function getResponsesPrettyAttribute(): string
    {
        return $this->formatJsonForPreview($this->responses);
    }

    public function getMatchingResultPrettyAttribute(): string
    {
        return $this->formatJsonForPreview(data_get($this->responses, ReceiptResponseKey::MATCHING_RESULT->value));
    }

    public function getStatusStrAttribute(): string
    {
        return $this->status->toString();
    }

    public function getSourceStrAttribute(): string
    {
        return $this->source->toString();
    }

    public function addResponse(ReceiptResponseKey|string $key, mixed $data): void
    {
        $responses = $this->responses ?? [];
        $responses[$key instanceof ReceiptResponseKey ? $key->value : $key] = $data;
        $this->update(['responses' => $responses]);
    }

    private function formatJsonForPreview(mixed $data): string
    {
        if (empty($data)) {
            return '-';
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = json_last_error() === JSON_ERROR_NONE ? $decoded : $data;
        }

        if (! is_array($data)) {
            return (string) $data;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReceiptPhoto::class);
    }
}
