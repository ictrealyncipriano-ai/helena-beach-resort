<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'reference_code', 'name', 'email', 'phone', 'check_in', 'check_out',
        'pax', 'cottage_id', 'guest_id', 'message', 'status', 'source',
        'booking_type', 'total_amount', 'paid_at', 'paid_amount',
        'payment_method', 'paymongo_session_id', 'payment_failed_at',
    ];

    /**
     * Boot events: auto-generates reference code (HB-000001) on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry) {
            if (empty($inquiry->reference_code)) {
                $inquiry->reference_code = 'HB-'.str_pad((static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'paid_amount' => 'decimal:2',
            'payment_failed_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function hasFailedPayment(): bool
    {
        return $this->payment_failed_at !== null;
    }

    /**
     * Human-friendly label for the stored payment method.
     */
    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'qrph' => 'QR Ph',
            'gcash' => 'GCash',
            'paymaya' => 'Maya',
            'manual' => 'Manual',
            default => $this->payment_method ? ucfirst($this->payment_method) : 'Online',
        };
    }

    public function cottage(): BelongsTo
    {
        return $this->belongsTo(Cottage::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function scopePending($q)
    {
        $q->where('status', 'pending');
    }

    public function scopeConfirmed($q)
    {
        $q->where('status', 'confirmed');
    }

    public function scopeCancelled($q)
    {
        $q->where('status', 'cancelled');
    }

    public function scopeExpired($q)
    {
        $q->where('status', 'expired');
    }

    /**
     * Create CottageDateBlock rows for the inquiry's date range so the
     * cottage is held (reserved) as soon as the inquiry is submitted.
     */
    public function reserveBlocks(): void
    {
        $range = $this->dateRange();
        if (! $range) {
            return;
        }

        foreach ($range as $date) {
            CottageDateBlock::firstOrCreate(
                ['cottage_id' => $this->cottage_id, 'date' => $date],
                ['reason' => $this->reasonLabel('Pending')]
            );
        }
    }

    /**
     * Promote existing pending blocks to booked (creating any missing
     * rows, e.g. for legacy inquiries that predate reservation-on-submit).
     */
    public function bookBlocks(): void
    {
        $range = $this->dateRange();
        if (! $range) {
            return;
        }

        foreach ($range as $date) {
            CottageDateBlock::updateOrCreate(
                ['cottage_id' => $this->cottage_id, 'date' => $date],
                ['reason' => $this->reasonLabel('Booked')]
            );
        }
    }

    /**
     * Remove all blocks held by this inquiry (pending or booked).
     *
     * @param  array{cottage_id: ?int, check_in: ?string, check_out: ?string}|null  $original
     */
    public function releaseBlocks(?array $original = null): void
    {
        $cottageId = $original['cottage_id'] ?? $this->cottage_id;
        $checkIn = $original['check_in'] ?? $this->check_in?->format('Y-m-d');
        $checkOut = $original['check_out'] ?? ($this->check_out ?? $this->check_in)?->format('Y-m-d');

        if (! $cottageId || ! $checkIn) {
            return;
        }

        CottageDateBlock::where('cottage_id', $cottageId)
            ->whereBetween('date', [$checkIn, $checkOut])
            ->whereIn('reason', [$this->reasonLabel('Pending'), $this->reasonLabel('Booked')])
            ->delete();
    }

    private function reasonLabel(string $type): string
    {
        return "{$type}: {$this->reference_code}";
    }

    private function dateRange(): ?\Generator
    {
        if (! $this->cottage_id || ! $this->check_in) {
            return null;
        }

        $start = $this->check_in->copy();
        $end = $this->check_out ?? $this->check_in->copy();

        while ($start->lte($end)) {
            yield $start->format('Y-m-d');
            $start->addDay();
        }
    }
}
