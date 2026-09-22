<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'points_earned',
        'points_used',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'points_earned' => 'integer',
            'points_used' => 'integer',
        ];
    }

    /**
     * Get the user that owns this point history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the booking associated with this point history.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

