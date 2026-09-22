<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'day_of_week',
        'open_time',
        'close_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /**
     * Validate that close_time is after open_time.
     */
    protected static function booted(): void
    {
        static::saving(function (CourtSchedule $schedule) {
            if ($schedule->close_time <= $schedule->open_time) {
                throw new \InvalidArgumentException('close_time must be after open_time');
            }
        });
    }

    /**
     * Get the court that owns this schedule.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get the day name for display.
     */
    public function getDayNameAttribute(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return $days[$this->day_of_week] ?? '';
    }
}

