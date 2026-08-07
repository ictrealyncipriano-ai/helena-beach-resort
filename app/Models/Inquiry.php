<?php

namespace App\Models;

use App\Exceptions\BookingConflictException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquiry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_code', 'name', 'email', 'phone', 'check_in', 'check_out',
        'pax', 'cottage_id', 'guest_id', 'message', 'status', 'source',
        'booking_type', 'total_amount', 'paid_at', 'paid_amount',
        'payment_method', 'paymongo_session_id', 'payment_failed_at',
        'paymongo_payment_id', 'refunded_at', 'refund_amount',
        'expiry_warned_at',
    ];

    protected $hidden = [
        'token', 'paymongo_session_id', 'paymongo_payment_id',
    ];

    /**
     * Boot events: auto-generates a non-enumerable booking token and a
     * collision-resistant reference code (HB-XXXXXX) on creation.
     *
     * The token is intentionally NOT in $fillable so it can never be
     * mass-assigned from request input; it is only set here.
     */
    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry) {
            if (empty($inquiry->token)) {
                $inquiry->token = bin2hex(random_bytes(20));
            }

            if (empty($inquiry->reference_code)) {
                $inquiry->reference_code = static::generateReferenceCode();
            }

            $inquiry->assertDataIntegrity();
        });

        static::updating(function (Inquiry $inquiry) {
            $inquiry->assertDataIntegrity();
        });
    }

    /**
     * Cross-driver backstop for the DB-level CHECK constraints that are only
     * emitted on PostgreSQL. Guards the status/booking-type enums and the
     * check-out-after-check-in ordering on every driver so an invalid value
     * can never be persisted outside of Postgres either.
     */
    protected function assertDataIntegrity(): void
    {
        if ($this->status !== null && ! in_array($this->status, ['pending', 'confirmed', 'cancelled', 'expired'], true)) {
            throw new \InvalidArgumentException('Invalid inquiry status: '.$this->status);
        }

        if ($this->booking_type !== null && ! in_array($this->booking_type, ['day_tour', 'overnight'], true)) {
            throw new \InvalidArgumentException('Invalid booking type: '.$this->booking_type);
        }

        if ($this->check_in !== null && $this->check_out !== null && $this->check_out->lt($this->check_in)) {
            throw new \InvalidArgumentException('Check-out must be on or after check-in.');
        }
    }

    /**
     * Human-readable, collision-resistant reference code (10 hex chars).
     * Unique-violation retries are handled by the callers (see InquiryService).
     */
    public static function generateReferenceCode(): string
    {
        return 'HB-'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
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
            'refunded_at' => 'datetime',
            'refund_amount' => 'decimal:2',
            'expiry_warned_at' => 'datetime',
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

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
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

    public function dateBlocks(): HasMany
    {
        return $this->hasMany(CottageDateBlock::class);
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
     *
     * Throws a BookingConflictException when a date is already held by a
     * different inquiry or a manual admin block. A single SELECT detects any
     * conflict across the whole range before a single INSERT writes every
     * night, so no partial hold can ever be left behind on a mid-range
     * conflict. The (cottage_id, date) unique index then arbitrates the
     * concurrent-submission race at the database level: the write uses
     * INSERT ... ON CONFLICT DO NOTHING, so a racing submission can never
     * overwrite the first writer's block.
     */
    public function reserveBlocks(): void
    {
        $dates = $this->dateRange();
        if ($dates === []) {
            return;
        }

        $pendingReason = $this->reasonLabel('Pending');

        $conflict = CottageDateBlock::where('cottage_id', $this->cottage_id)
            ->whereIn('date', $dates)
            ->where(function ($q) use ($pendingReason) {
                $q->whereNull('reason')->orWhere('reason', '!=', $pendingReason);
            })
            ->orderBy('date')
            ->first();

        if ($conflict) {
            throw new BookingConflictException(
                "The cottage is already reserved on {$conflict->date} ({$conflict->reason})."
            );
        }

        $this->insertBlocks(
            $this->blockRows($pendingReason, $dates),
            $dates,
            'These dates are no longer available.'
        );
    }

    /**
     * Promote existing pending blocks to booked (creating any missing
     * rows, e.g. for legacy inquiries that predate reservation-on-submit).
     *
     * Throws a BookingConflictException instead of overwriting a block that
     * belongs to a different inquiry or a manual admin block.
     */
    public function bookBlocks(): void
    {
        $dates = $this->dateRange();
        if ($dates === []) {
            return;
        }

        $allowedReasons = [
            $this->reasonLabel('Pending'),
            $this->reasonLabel('Booked'),
        ];

        $conflict = CottageDateBlock::where('cottage_id', $this->cottage_id)
            ->whereIn('date', $dates)
            ->where(function ($q) use ($allowedReasons) {
                $q->whereNull('reason')->orWhereNotIn('reason', $allowedReasons);
            })
            ->orderBy('date')
            ->first();

        if ($conflict) {
            throw new BookingConflictException(
                "Cottage is already reserved on {$conflict->date} by another booking ({$conflict->reason})."
            );
        }

        $this->insertBlocks(
            $this->blockRows($this->reasonLabel('Booked'), $dates),
            $dates,
            'These dates are no longer available.',
            $this->reasonLabel('Booked')
        );
    }

    /**
     * Write every block row in one atomic INSERT, relying on the
     * (cottage_id, date) unique index to arbitrate a concurrent submission.
     *
     * INSERT ... ON CONFLICT DO NOTHING (insertOrIgnore) is used instead of
     * an upsert so a racing writer can never OVERWRITE the first writer's
     * block: a conflicted row is silently skipped and the other writer's
     * inquiry_id/reason stay intact. When fewer rows are inserted than
     * expected, the surviving rows are checked — a block belonging to a
     * different inquiry or to a manual admin block (inquiry_id null) is a
     * real conflict and triggers a BookingConflictException (the caller's
     * DB::transaction then rolls back). Blocks already held by THIS inquiry
     * (e.g. re-confirming a stay that still carries its own pending hold)
     * are not conflicts; when $promoteReason is given they are updated in
     * place so confirmation still promotes Pending -> Booked.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string[]  $dates
     */
    private function insertBlocks(array $rows, array $dates, string $message, ?string $promoteReason = null): void
    {
        $inserted = CottageDateBlock::insertOrIgnore($rows);

        if ($inserted === count($rows)) {
            return;
        }

        $foreignConflict = CottageDateBlock::where('cottage_id', $this->cottage_id)
            ->whereIn('date', $dates)
            ->where(function ($q) {
                if ($this->id) {
                    $q->whereNull('inquiry_id')
                        ->orWhere('inquiry_id', '!=', $this->id);
                }
            })
            ->exists();

        if ($foreignConflict) {
            throw new BookingConflictException($message);
        }

        // Every skipped row belongs to this inquiry (e.g. a pending hold being
        // confirmed). Promote those self-owned blocks to the target reason —
        // only this inquiry's rows are touched, so the write is race-safe.
        if ($promoteReason !== null && $this->id) {
            CottageDateBlock::where('cottage_id', $this->cottage_id)
                ->whereIn('date', $dates)
                ->where('inquiry_id', $this->id)
                ->update(['reason' => $promoteReason, 'updated_at' => now()]);
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

        $query = CottageDateBlock::where('cottage_id', $cottageId)
            ->whereBetween('date', [$checkIn, $checkOut]);

        // Blocks written before the inquiry_id column existed still carry the
        // reference-code reason, so match either the FK or the legacy reason.
        if ($this->id) {
            $query->where(function ($q) {
                $q->where('inquiry_id', $this->id)
                  ->orWhereIn('reason', [
                      $this->reasonLabel('Pending'),
                      $this->reasonLabel('Booked'),
                  ]);
            });
        } else {
            $query->whereIn('reason', [
                $this->reasonLabel('Pending'),
                $this->reasonLabel('Booked'),
            ]);
        }

        $query->delete();
    }

    /**
     * Build the insert payload for one stay. `inquiry_id` links every block
     * to the inquiry that holds it so downstream queries never have to parse
     * the human-readable reason string.
     *
     * @param  string[]  $dates
     * @return array<int, array<string, mixed>>
     */
    private function blockRows(string $reason, array $dates): array
    {
        $now = now();

        return array_map(fn (string $date) => [
            'cottage_id' => $this->cottage_id,
            'date' => $date,
            'reason' => $reason,
            'inquiry_id' => $this->id,
            'created_at' => $now,
            'updated_at' => $now,
        ], $dates);
    }

    private function reasonLabel(string $type): string
    {
        return "{$type}: {$this->reference_code}";
    }

    /**
     * Every calendar date covered by the stay, inclusive of check-in and
     * check-out (a day tour without a check-out covers only check-in).
     *
     * @return string[]
     */
    private function dateRange(): array
    {
        if (! $this->cottage_id || ! $this->check_in) {
            return [];
        }

        $dates = [];
        $cursor = $this->check_in->copy();
        $end = $this->check_out ?? $this->check_in->copy();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dates;
    }
}
