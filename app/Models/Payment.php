<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'status',
        'paid_at',
        'invoice_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the booking that this payment belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Generate a unique sequential invoice number (e.g., INV-20260922-0001).
     */
    public static function generateInvoiceNumber(): string
    {
        $datePrefix = 'INV-' . now()->format('Ymd') . '-';
        $lastPayment = self::where('invoice_number', 'like', $datePrefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastPayment) {
            $lastSeq = (int) substr($lastPayment->invoice_number, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $datePrefix . sprintf('%04d', $nextSeq);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}

