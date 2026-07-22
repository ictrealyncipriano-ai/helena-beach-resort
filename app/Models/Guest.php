<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'notes',
        'total_stays',
        'last_stay_at',
    ];

    protected function casts(): array
    {
        return [
            'last_stay_at' => 'date',
        ];
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }
}
