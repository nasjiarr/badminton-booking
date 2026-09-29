<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Get the court affected by this closure, or null if all courts are affected.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get the admin who scheduled this closure.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to find closures affecting a specific court (or all courts) on a specific date.
     */
    public function scopeForCourtAndDate($query, ?int $courtId, string $date)
    {
        return $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function ($q) use ($courtId) {
                $q->whereNull('court_id');
                if ($courtId !== null) {
                    $q->orWhere('court_id', $courtId);
                }
            });
    }

    /**
     * Scope to find closures active on a specific date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    /**
     * Scope for active or upcoming closures.
     */
    public function scopeUpcomingOrActive($query)
    {
        return $query->whereDate('end_date', '>=', now()->format('Y-m-d'))
            ->orderBy('start_date')
            ->orderBy('start_time');
    }

    /**
     * Calculate total days covered by this closure.
     */
    public function getDaysCountAttribute(): int
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        return max(1, $start->diffInDays($end) + 1);
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'tournament' => 'Turnamen',
            'maintenance' => 'Perawatan Lapangan',
            'holiday' => 'Libur Gelanggang',
            'special_event' => 'Acara Khusus',
            default => 'Penutupan',
        };
    }
}
