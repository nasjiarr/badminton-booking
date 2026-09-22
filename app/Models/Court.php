<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price_per_hour',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the schedules for this court.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(CourtSchedule::class);
    }

    /**
     * Get the bookings for this court.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the recurring bookings for this court.
     */
    public function recurringBookings(): HasMany
    {
        return $this->hasMany(RecurringBooking::class);
    }
}

