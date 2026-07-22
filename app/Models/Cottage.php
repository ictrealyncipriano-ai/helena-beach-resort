<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Cottage extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'capacity',
        'rate_daytour', 'rate_overnight', 'is_available', 'sort_order'
    ];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn ($cottage) => $cottage->slug = $cottage->slug ?: Str::slug($cottage->name));
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CottagePhoto::class)->orderBy('sort_order');
    }

    public function amenities(): HasMany
    {
        return $this->hasMany(CottageAmenity::class);
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(CottagePhoto::class)->where('is_primary', true);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function dateBlocks(): HasMany
    {
        return $this->hasMany(CottageDateBlock::class);
    }

    public function isAvailableOn(string $date): bool
    {
        return !$this->dateBlocks()->where('date', $date)->exists();
    }

    public function scopeAvailableOn($q, string $date)
    {
        $q->whereDoesntHave('dateBlocks', fn ($q) => $q->where('date', $date));
    }
}
