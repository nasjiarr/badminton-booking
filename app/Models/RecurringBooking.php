<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'court_id',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Validate that end_time is after start_time.
     */
    protected static function booted(): void
    {
        static::saving(function (RecurringBooking $recurringBooking) {
            if ($recurringBooking->end_time <= $recurringBooking->start_time) {
                throw new \InvalidArgumentException('end_time must be after start_time');
            }
        });
    }

    /**
     * Get the user that owns this recurring booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the court for this recurring booking.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get the individual bookings generated from this recurring booking.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}

