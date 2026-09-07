<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CottageDateBlock extends Model
{
    /**
     * Tracks dates when a cottage is booked/unavailable.
     * Created when an inquiry is created or confirmed to prevent double bookings.
     */
    protected $fillable = [
        'cottage_id', 'date', 'reason', 'inquiry_id',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /**
     * Normalize every Eloquent write to Y-m-d so model writes match the
     * raw query-builder inserts in ManagesDateBlocks::blockRows().
     * Without this, the `date` cast serializes to a datetime string on
     * drivers without a native DATE type (SQLite), and the conflict
     * pre-checks (`whereIn('date', Y-m-d strings)`) silently miss
     * admin-created manual blocks — allowing double bookings.
     */
    public function setDateAttribute(mixed $value): void
    {
        $this->attributes['date'] = $value === null
            ? null
            : Carbon::parse($value)->format('Y-m-d');
    }

    public function cottage(): BelongsTo
    {
        return $this->belongsTo(Cottage::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function scopeFuture(Builder $q): Builder
    {
        return $q->where('date', '>=', now()->startOfDay());
    }
}
