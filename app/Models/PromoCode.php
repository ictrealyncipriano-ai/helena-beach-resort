<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_amount', 'valid_from', 'valid_until',
        'usage_limit', 'used_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Normalize the code to uppercase on save so lookups are case-insensitive.
     */
    protected static function booted(): void
    {
        static::saving(function (PromoCode $promo) {
            $promo->code = static::normalize($promo->code);
        });
    }

    public static function normalize(string $code): string
    {
        return Str::upper(Str::trim($code));
    }

    public function isPercent(): bool
    {
        return $this->type === 'percent';
    }

    /**
     * Whether the code is active, inside its validity window and under its
     * usage limit. Amount-independent checks only.
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from !== null && $this->valid_from->gt($now)) {
            return false;
        }

        if ($this->valid_until !== null && $this->valid_until->lt($now)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Resolve a code from a guest-supplied string against the database
     * (case-insensitive) including the minimum-amount check, or null when
     * invalid/inapplicable.
     *
     * @param  float|string|null  $subtotal
     */
    public static function findUsable(?string $code, mixed $subtotal = null): ?self
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $promo = static::where('code', static::normalize($code))->first();
        if (! $promo || ! $promo->isCurrentlyValid()) {
            return null;
        }

        if ($subtotal !== null && $promo->min_amount !== null && (float) $subtotal < (float) $promo->min_amount) {
            return null;
        }

        return $promo;
    }

    /**
     * Discount for the given subtotal, never exceeding the amount payable.
     */
    public function discountFor(mixed $subtotal): string
    {
        $subtotal = max(0, (float) $subtotal);

        $discount = $this->isPercent()
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return formatPrice(min($discount, $subtotal), 2, false);
    }

    /**
     * Human-friendly display value, e.g. "₱500.00" or "10%".
     */
    public function valueLabel(): string
    {
        return $this->isPercent()
            ? rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.').'%'
            : formatPrice($this->value);
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Atomically increment the usage counter. Safe to call repeatedly.
     */
    public function consume(): void
    {
        static::whereKey($this->id)->increment('used_count');
    }
}
