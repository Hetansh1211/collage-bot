<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const MAX_CREDIT_POINTS = 300;
    public const CREDIT_REFRESH_HOURS = 8;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'credit_points',
        'last_credit_refresh',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'credit_points' => 'integer',
            'last_credit_refresh' => 'datetime',
        ];
    }

    /**
     * Get the next credit refresh time
     */
    public function getNextRefreshTime()
    {
        $lastRefresh = $this->last_credit_refresh ?? now();

        return $lastRefresh->copy()->addHours(self::CREDIT_REFRESH_HOURS);
    }

    /**
     * Refresh credits when the 8-hour window has passed.
     */
    public function refreshCreditsIfDue(): bool
    {
        if (
            $this->last_credit_refresh === null ||
            $this->last_credit_refresh->copy()->addHours(self::CREDIT_REFRESH_HOURS)->isPast()
        ) {
            $this->forceFill([
                'credit_points' => self::MAX_CREDIT_POINTS,
                'last_credit_refresh' => now(),
            ])->save();

            return true;
        }

        return false;
    }

    public function nextCreditRefreshLabel(): string
    {
        return $this->getNextRefreshTime()->format('M d, Y h:i A');
    }

    /**
     * Check if credits are depleted
     */
    public function isCreditsExpired(): bool
    {
        return $this->credit_points <= 0;
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
