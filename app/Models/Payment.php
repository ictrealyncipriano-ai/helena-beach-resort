<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WP-1: financial ledger for a booking. Inquiry remains the booking record;
 * Payment is the money record. Written alongside (dual-write) the existing
 * inquiries.* summary columns — never the source of truth yet.
 */
class Payment extends Model
{
    public const PROVIDER_PAYMONGO = 'paymongo';
    public const PROVIDER_MANUAL = 'manual';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_BALANCE = 'balance';
    public const TYPE_FULL = 'full_payment';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPES = [
        self::TYPE_DEPOSIT,
        self::TYPE_BALANCE,
        self::TYPE_FULL,
        self::TYPE_REFUND,
        self::TYPE_ADJUSTMENT,
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDING = 'refunding';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REQUIRES_REFUND = 'requires_refund';

    protected $fillable = [
        'inquiry_id', 'provider', 'provider_payment_id', 'provider_checkout_id',
        'provider_refund_id', 'method', 'type', 'amount', 'currency', 'status',
        'paid_at', 'refunded_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /**
     * Idempotent paid-row writer. When a provider payment id is known the
     * row is deduplicated on it (firstOrCreate); manual rows (no provider
     * id) are plain creates — callers own their idempotency there.
     */
    public static function recordPaid(
        Inquiry $inquiry,
        string $amount,
        ?string $method = null,
        ?string $providerPaymentId = null,
        ?string $checkoutId = null,
        string $type = self::TYPE_FULL,
        string $provider = self::PROVIDER_PAYMONGO,
    ): self {
        $attributes = [
            'inquiry_id' => $inquiry->id,
            'provider' => $provider,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'PHP',
            'status' => self::STATUS_PAID,
            'method' => $method,
            'provider_payment_id' => $providerPaymentId,
            'provider_checkout_id' => $checkoutId,
            'paid_at' => now(),
        ];

        if ($providerPaymentId !== null) {
            return static::firstOrCreate(
                ['provider_payment_id' => $providerPaymentId],
                $attributes
            );
        }

        return static::create($attributes);
    }

    /**
     * Classify a received amount as deposit / balance / full_payment from
     * the pre-write locked inquiry state. Pure function for testability.
     */
    public static function classifyType(Inquiry $locked, bool $fullyPaid, bool $depositCovered): string
    {
        if ($fullyPaid) {
            return (float) ($locked->amount_paid ?? 0) > 0
                ? self::TYPE_BALANCE
                : self::TYPE_FULL;
        }

        if ($depositCovered && $locked->hasDeposit()) {
            return self::TYPE_DEPOSIT;
        }

        return self::TYPE_BALANCE;
    }
}
