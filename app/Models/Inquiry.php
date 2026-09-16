<?php

namespace App\Models;

use App\Concerns\ManagesDateBlocks;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        'refund_amount', 'refund_status', 'refund_attempts', 'refund_last_error', 'expiry_warned_at',
        'deposit_amount', 'amount_paid', 'deposit_paid_at',
        'fully_paid_at', 'payment_pending_amount', 'payment_pending_at',
        'payment_proof_path', 'payment_proof_status',
        'payment_proof_submitted_at', 'payment_proof_reviewed_at',
        'payment_proof_review_note',
    ];

    protected $hidden = [
        'token', 'paymongo_session_id', 'paymongo_payment_id',
    ];

    /**
     * Boot events: auto-generates a non-enumerable booking token and a
     * collision-resistant reference code (PREFIX-XXXXXXXXXX) on creation.
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
     * Per-resort booking reference prefix (BOOKING_REF_PREFIX, default HB-).
     * Centralized here so Admin\CottageController's date-block guards track
     * the same prefix new bookings are generated with. Only affects newly
     * generated codes — existing references keep working unchanged.
     */
    public static function referencePrefix(): string
    {
        $prefix = (string) config('booking.reference_prefix', 'HB-');

        return preg_match('/^[A-Z]{1,4}-$/', $prefix) ? $prefix : 'HB-';
    }

    /**
     * Human-readable, collision-resistant reference code
     * (prefix + 10 hex chars, e.g. HB-XXXXXXXXXX).
     * Unique-violation retries are handled by the callers (see InquiryService).
     */
    public static function generateReferenceCode(): string
    {
        return static::referencePrefix().strtoupper(bin2hex(random_bytes(5)));
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
            'payment_pending_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refund_amount' => 'decimal:2',
            'refund_attempts' => 'integer',
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
     *
     * Money migration (Phase 7.3): exact comparison; null guard and
     * positive-amount semantics preserved.
     */
    public function hasDeposit(): bool
    {
        return $this->deposit_amount !== null && Money::cmp((string) $this->deposit_amount, '0.00') > 0;
    }

    /**
     * Whether the configured deposit has been settled (in full). A booking
     * without a configured deposit is never "deposit paid".
     *
     * Money migration (Phase 7.3): exact comparison; hasDeposit() gate and
     * timestamp short-circuit order preserved.
     */
    public function isDepositPaid(): bool
    {
        if (! $this->hasDeposit()) {
            return false;
        }

        return $this->deposit_paid_at !== null
            || Money::cmp(
                (string) ($this->amount_paid ?? '0.00'),
                (string) $this->deposit_amount
            ) >= 0;
    }

    /**
     * The amount still owed after everything received so far.
     *
     * Money migration: exact string subtraction (no binary float),
     * clamped at zero, '0.00'-form string contract preserved.
     */
    public function balanceDue(): string
    {
        $diff = Money::sub(
            (string) ($this->total_amount ?? '0.00'),
            (string) ($this->amount_paid ?? '0.00')
        );

        return Money::cmp($diff, '0.00') < 0 ? '0.00' : $diff;
    }

    /**
     * Amount the guest should be asked to pay right now: the outstanding
     * deposit when a deposit is set and unpaid, otherwise the remaining
     * balance.
     */
    public function amountDueNow(): string
    {
        if ($this->hasDeposit() && ! $this->isDepositPaid()) {
            $diff = Money::sub(
                (string) ($this->deposit_amount ?? '0.00'),
                (string) ($this->amount_paid ?? '0.00')
            );

            return Money::cmp($diff, '0.00') < 0 ? '0.00' : $diff;
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
        // Unclamped by contract: negatives/overpayments pass through verbatim.
        return Money::from($this->amount_paid ?? '0.00');
    }

    /**
     * Whether any money has been collected at all (deposit included).
     *
     * Money migration (Phase 7.3): exact comparison over the already
     * Money-normalized collectedAmount(); no change to its calculation.
     */
    public function hasPayments(): bool
    {
        return Money::cmp($this->collectedAmount(), '0.00') > 0;
    }

    /**
     * Remaining balance: total minus collected, never negative.
     *
     * Kept structurally independent from balanceDue(): this path goes via
     * collectedAmount(), now with exact string math on each leg.
     */
    public function outstandingBalance(): string
    {
        $diff = Money::sub(
            (string) ($this->total_amount ?? '0.00'),
            $this->collectedAmount()
        );

        return Money::cmp($diff, '0.00') < 0 ? '0.00' : $diff;
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
     * Concurrency-safe: the whole read-modify-write (balance re-check,
     * summary update, ledger insert) runs inside one DB::transaction under
     * a row lock, so two serialized submissions can never lose an update
     * or drift the summary away from the ledger sum. The over-balance
     * check is re-applied on the locked row (the controller's validate()
     * stays for UX only) and throws a ValidationException when the amount
     * no longer fits the outstanding balance.
     *
     * Idempotency (no ledger redesign): pass the same $idempotencyKey for
     * retries of one logical payment and only the first attempt writes —
     * the key is carried in the ledger row's metadata (existing nullable
     * json column). Without a key every call records: two serialized
     * legitimate payments both land and summary == ledger sum.
     *
     * @return bool whether this payment completed the booking's full total
     */
    public function recordManualPayment(string $amount, string $method = self::METHOD_MANUAL, ?string $idempotencyKey = null): bool
    {
        return DB::transaction(function () use ($amount, $method, $idempotencyKey) {
            $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

            // Money migration: exact string math from here on. $paymentAmount
            // is the normalized '0.00'-form string; the 0.005 over-balance
            // gate below is preserved structurally (see note there).
            $paymentAmount = Money::from($amount);

            if (Money::cmp($paymentAmount, '0.00') <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The amount must be greater than zero.',
                ]);
            }

            $total = (string) $locked->total_amount;
            $priorPaid = (string) ($locked->amount_paid ?? '0.00');
            $balance = Money::sub($total, $priorPaid);
            $flooredBalance = Money::cmp($balance, '0.00') < 0 ? '0.00' : $balance;

            // Idempotent retry first: a repeated submission of one logical
            // payment returns the stored outcome even when the balance no
            // longer fits the amount (e.g. retrying a full settlement shows
            // balance 0). Checked inside the lock so a serialized retry sees
            // the first attempt's committed ledger row.
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $duplicate = Payment::where('inquiry_id', $locked->id)
                    ->where('provider', Payment::PROVIDER_MANUAL)
                    ->orderByDesc('id')
                    ->limit(25)
                    ->get()
                    ->firstWhere(fn (Payment $row) => ($row->metadata['idempotency_key'] ?? null) === $idempotencyKey);

                if ($duplicate) {
                    $this->refresh();

                    return Money::cmp((string) ($locked->amount_paid ?? '0.00'), (string) $locked->total_amount) >= 0;
                }
            }

            // Preserved 0.005 gate: operands are 2-decimal strings so their
            // exact difference is a whole cent value, making `> 0` identical
            // to the legacy `> 0.005` float comparison. Only the tolerance
            // representation changed, never the accept/reject boundary.
            if (Money::cmp(Money::sub($paymentAmount, $flooredBalance), '0.00') > 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The amount exceeds the outstanding balance of '.formatPrice($flooredBalance).'.',
                ]);
            }

            $newAmountPaid = Money::add($priorPaid, $paymentAmount);

            $fullyPaid = Money::cmp($newAmountPaid, $total) >= 0;
            $depositCovered = $locked->hasDeposit()
                && Money::cmp($newAmountPaid, (string) $locked->deposit_amount) >= 0;

            // Classify from the pre-write locked state (priorPaid > 0 means
            // this leg settles a balance, not a first full payment).
            $type = Payment::classifyType($locked, $fullyPaid, $depositCovered);

            $locked->update([
                'amount_paid' => $newAmountPaid,
                'deposit_paid_at' => $depositCovered && ! $locked->isDepositPaid()
                    ? now()
                    : $locked->deposit_paid_at,
                'fully_paid_at' => $fullyPaid ? now() : $locked->fully_paid_at,
                'payment_method' => $method,
            ]);

            // WP-1 dual-write: mirror the settlement into the ledger inside
            // the same locked transaction. Manual rows carry no provider id,
            // so this is a plain create.
            Payment::create([
                'inquiry_id' => $locked->id,
                'provider' => Payment::PROVIDER_MANUAL,
                'method' => $method,
                'type' => $type,
                'amount' => Money::from($amount),
                'currency' => 'PHP',
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
                'metadata' => $idempotencyKey !== null && $idempotencyKey !== ''
                    ? ['idempotency_key' => $idempotencyKey]
                    : null,
            ]);

            $this->refresh();

            return $fullyPaid;
        });
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

    /**
     * WP-1: financial ledger rows for this booking. Additive — the
     * inquiries.* summary columns remain the operational source of truth
     * until later phases.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
