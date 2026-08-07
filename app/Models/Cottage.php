<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Descriptions are rendered with {!! !!} on the public cottage page, so
     * they are sanitized through an allow-list on every write. This keeps
     * admin-entered rich text (<p>, <strong>, …) but strips any script,
     * event-handler attribute, or javascript: URL before it can be persisted.
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => app(HtmlSanitizer::class)->sanitize($value),
        );
    }

    /**
     * Boot events: auto-generates URL slug from cottage name.
     */
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
}
