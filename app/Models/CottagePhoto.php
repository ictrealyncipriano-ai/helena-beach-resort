<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CottagePhoto extends Model
{
    protected $fillable = ['cottage_id', 'photo_path', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function cottage(): BelongsTo
    {
        return $this->belongsTo(Cottage::class);
    }
}
