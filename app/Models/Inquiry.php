<?php

namespace App\Models;

use App\Concerns\ManagesDateBlocks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquiry extends Model
{
    use SoftDeletes;
    use ManagesDateBlocks;

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    // Booking types
    public const TYPE_DAY_TOUR = 'day_tour';
    public const TYPE_OVERNIGHT = 'overnight';
    public const BOOKING_TYPES = [self::TYPE_DAY_TOUR, self::TYPE_OVERNIGHT];

    // Payment proof statuses
    public const PROOF_NONE = 'none';
    public const PROOF_PENDING = 'pending';
    public const PROOF_APPROVED = 'approved';
    public const PROOF_REJECTED = 'rejected';

    // Payment methods
    public const METHOD_MANUAL = 'manual';
    public const METHOD_QRPH = 'qrph';
    public const METHOD_GCASH = 'gcash';
    public const METHOD_PAYMAYA = 'paymaya';

    // Sources
    public const SOURCE_WALKIN = 'walk-in';
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_BOOKING = 'booking';
    public const SOURCE_GUEST = 'guest';

    protected $fillable = [
        'reference_code', 'name', 'email', 'phone', 'check_in', 'check_out',
        'pax', 'cottage_id', 'guest_id', 'message', 'status', 'source',
        'booking_type', 'total_amount', 'promo_code_id', 'discount_amount',
        'payment_method', 'paymongo_session_id',
        'payment_failed_at', 'paymongo_payment_id', 'refunded_at',
        'refund_amount', 'expiry_warned_at',
        'deposit_amount', 'amount_paid', 'deposit_paid_at',
        'fully_paid_at', 'payment_pending_amount',
        'payment_proof_path', 'payment_proof_status',
        'payment_proof_submitted_at', 'payment_proof_reviewed_at',
        'payment_proof_review_note',
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
        if ($this->status !== null && ! in_array($this->status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid inquiry status: '.$this->status);
        }

        if ($this->booking_type !== null && ! in_array($this->booking_type, self::BOOKING_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid booking type: '.$this->booking_type);
        }

        if ($this->check_in !== null && $this->check_out !== null) {
            $checkIn = $this->check_in instanceof \DateTimeInterface
                ? \Carbon\Carbon::parse($this->check_in)
                : \Carbon\Carbon::parse((string) $this->check_in);
            $checkOut = $this->check_out instanceof \DateTimeInterface
                ? \Carbon\Carbon::parse($this->check_out)
                : \Carbon\Carbon::parse((string) $this->check_out);

            if ($checkOut->lt($checkIn)) {
                throw new \InvalidArgumentException('Check-out must be on or after check-in.');
            }
        }
    }

    /**
     * Human-readable, collision-resistant reference code (HB- + 10 hex chars).
     * Unique-violation retries are handled by the callers (see InquiryService).
     */
    public static function generateReferenceCode(): string
    {
        return 'HB-'.strtoupper(bin2hex(random_bytes(5)));
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'deposit_paid_at' => 'datetime',
            'fully_paid_at' => 'datetime',
            'payment_pending_amount' => 'decimal:2',
            'payment_failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refund_amount' => 'decimal:2',
            'expiry_warned_at' => 'datetime',
            'payment_proof_submitted_at' => 'datetime',
            'payment_proof_reviewed_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->fully_paid_at !== null;
    }

    /**
     * Whether a deposit is configured for this booking.
     */
    public function hasDeposit(): bool
    {
        return $this->deposit_amount !== null && (float) $this->deposit_amount > 0;
    }

    /**
     * Whether the configured deposit has been settled (in full). A booking
     * without a configured deposit is never "deposit paid".
     */
    public function isDepositPaid(): bool
    {
        if (! $this->hasDeposit()) {
            return false;
        }

        return $this->deposit_paid_at !== null
            || (float) ($this->amount_paid ?? 0) >= (float) $this->deposit_amount;
    }

    /**
     * The amount still owed after everything received so far.
     */
    public function balanceDue(): string
    {
        $total = (float) $this->total_amount;
        $paid = (float) ($this->amount_paid ?? 0);

        return formatPrice(max($total - $paid, 0), 2, false);
    }

    /**
     * Amount the guest should be asked to pay right now: the outstanding
     * deposit when a deposit is set and unpaid, otherwise the remaining
     * balance.
     */
    public function amountDueNow(): string
    {
        if ($this->hasDeposit() && ! $this->isDepositPaid()) {
            $deposit = (float) $this->deposit_amount;
            $paid = (float) ($this->amount_paid ?? 0);

            return formatPrice(max($deposit - $paid, 0), 2, false);
        }

        return $this->balanceDue();
    }

    public function hasFailedPayment(): bool
    {
        return $this->payment_failed_at !== null;
    }

    /**
     * Whether a proof of a manual payment has been submitted and is awaiting
     * (or under) admin review.
     */
    public function hasPendingPaymentProof(): bool
    {
        return $this->payment_proof_status === self::PROOF_PENDING;
    }

    /**
     * Whether the most recently submitted payment proof was approved.
     */
    public function hasApprovedPaymentProof(): bool
    {
        return $this->payment_proof_status === self::PROOF_APPROVED;
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    /**
     * Total money actually received so far (online payments + recorded
     * manual settlements). This — never amount_paid/total_amount — is the
     * basis for refunds and "how much did the guest give us" questions.
     */
    public function collectedAmount(): string
    {
        $collected = (float) ($this->amount_paid ?? 0);

        return formatPrice($collected, 2, false);
    }

    /**
     * Whether any money has been collected at all (deposit included).
     */
    public function hasPayments(): bool
    {
        return (float) $this->collectedAmount() > 0;
    }

    /**
     * Remaining balance: total minus collected, never negative.
     */
    public function outstandingBalance(): string
    {
        $balance = (float) ($this->total_amount ?? 0) - (float) $this->collectedAmount();

        return formatPrice(max(0, $balance), 2, false);
    }

    /**
     * Reverse the stay count that markConfirmed() recorded on the guest
     * profile, guarding against a negative counter.
     */
    public function reverseStay(): void
    {
        if ($this->guest && $this->guest->total_stays > 0) {
            $this->guest->decrement('total_stays');
        }
    }

    /**
     * The amount that should be handed back when cancelling/refunding:
     * exactly what was collected (legacy rows included).
     */
    public function refundableAmount(): string
    {
        return $this->collectedAmount();
    }

    /**
     * Record a manually-collected settlement (cash / bank transfer marked
     * by an admin). Adds to amount_paid and derives deposit/full-payment
     * timestamps from coverage — never assumes the booking was settled in
     * full unless the running total actually covers total_amount.
     *
     * @return bool whether this payment completed the booking's full total
     */
    public function recordManualPayment(string $amount, string $method = self::METHOD_MANUAL): bool
    {
        $newAmountPaid = formatPrice(
            (float) ($this->amount_paid ?? 0) + (float) $amount,
            2, false
        );

        $fullyPaid = (float) $newAmountPaid >= (float) $this->total_amount;
        $depositCovered = $this->hasDeposit()
            && (float) $newAmountPaid >= (float) $this->deposit_amount;

        $this->update([
            'amount_paid' => $newAmountPaid,
            'deposit_paid_at' => $depositCovered && ! $this->isDepositPaid()
                ? now()
                : $this->deposit_paid_at,
            'fully_paid_at' => $fullyPaid ? now() : $this->fully_paid_at,
            'payment_method' => $method,
        ]);

        return $fullyPaid;
    }

    /**
     * Human-friendly label for the stored payment method.
     */
    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::METHOD_QRPH => 'QR Ph',
            self::METHOD_GCASH => 'GCash',
            self::METHOD_PAYMAYA => 'Maya',
            self::METHOD_MANUAL => 'Manual',
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

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function dateBlocks(): HasMany
    {
        return $this->hasMany(CottageDateBlock::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeCancelled(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CANCELLED);
    }

    public function scopeExpired(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_EXPIRED);
    }
}
