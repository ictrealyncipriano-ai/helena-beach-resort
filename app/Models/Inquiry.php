<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'reference_code', 'name', 'email', 'phone', 'check_in', 'check_out',
        'pax', 'cottage_id', 'guest_id', 'message', 'status', 'source'
    ];

    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry) {
            if (empty($inquiry->reference_code)) {
                $inquiry->reference_code = 'HB-' . str_pad((static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
        });
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
}
