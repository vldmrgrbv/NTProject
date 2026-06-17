<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\NTApiService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

#[Guarded([])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_authorized' => 'boolean',
            'is_whitelisted' => 'boolean',
        ];
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }

    public function maxUser(): HasOne
    {
        return $this->hasOne(MaxUser::class);
    }

    public function getNewTokenAttribute(): string
    {
        $this->tokens()->delete();

        return $this->createToken('auth_token')->plainTextToken;
    }

    public function checkAuthCode(): void
    {
        $this->whereNotNull('auth_code')
            ->where('updated_at', '<', now()->subMinutes(config('nt.limits.auth_code_minutes')))
            ->update([
                'auth_code' => null,
                'auth_code_attempts' => 0,
            ]);
        $this->refresh();
    }

    public function getNamePhoneStr(): string
    {
        if (!$this->name && !$this->phone) {
            return (string) $this->id;
        }

        return $this->name ? $this->phone.' | '.$this->name : $this->phone;
    }

    public function getMaxIdAttribute(): ?int
    {
        return $this->maxUser?->max_id;
    }

    public function getScores(): int
    {
        if (! $this->phone) {
            return 0;
        }

        try {
            $ntApi = app(NTApiService::class);
            $response = $ntApi->getScores($this->phone);

            if (data_get($response, 'status') === 'success') {
                return (int) data_get($response, 'data', 0);
            }
        } catch (\Throwable $e) {
            Log::error('Error fetching scores for user '.$this->id.': '.$e->getMessage());
        }

        return 0;
    }
}
