<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CottageDateBlock extends Model
{
    protected $fillable = [
        'cottage_id', 'date', 'reason',
    ];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    public function cottage(): BelongsTo
    {
        return $this->belongsTo(Cottage::class);
    }

    public function scopeFuture($q)
    {
        $q->where('date', '>=', now()->startOfDay());
    }
}
