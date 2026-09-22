<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'court_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'total_price',
        'notes',
        'is_recurring',
        'recurring_booking_id',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'total_price' => 'decimal:2',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * Validate that end_time is after start_time.
     */
    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {
            if ($booking->end_time <= $booking->start_time) {
                throw new \InvalidArgumentException('end_time must be after start_time');
            }
        });
    }

    /**
     * Get the user that owns this booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the court for this booking.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get the recurring booking that generated this booking.
     */
    public function recurringBooking(): BelongsTo
    {
        return $this->belongsTo(RecurringBooking::class);
    }

    /**
     * Get the payment for this booking.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the point histories for this booking.
     */
    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }
}

