<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points',
        'tier',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    /**
     * Get the user that owns this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine tier based on total points.
     * Bronze: 0-99, Silver: 100-299, Gold: 300+
     */
    public static function calculateTier(int $points): string
    {
        if ($points >= 300) {
            return 'gold';
        }

        if ($points >= 100) {
            return 'silver';
        }

        return 'bronze';
    }

    /**
     * Get booking discount percentage based on tier.
     * Bronze: 0%, Silver: 5%, Gold: 10%
     */
    public function getDiscountPercentageAttribute(): int
    {
        return match ($this->tier) {
            'gold' => 10,
            'silver' => 5,
            default => 0,
        };
    }
}

