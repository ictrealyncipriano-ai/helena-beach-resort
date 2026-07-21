<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CottageAmenity extends Model
{
    protected $fillable = ['cottage_id', 'name', 'icon'];

    public function cottage(): BelongsTo
    {
        return $this->belongsTo(Cottage::class);
    }
}
