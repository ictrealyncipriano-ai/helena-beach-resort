<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use App\Support\PublicCache;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Cottage extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'capacity',
        'rate_daytour', 'rate_overnight', 'is_available', 'sort_order',
        'peak_start', 'peak_end', 'peak_rate_daytour', 'peak_rate_overnight',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'peak_start' => 'date',
            'peak_end' => 'date',
            'peak_rate_daytour' => 'decimal:2',
            'peak_rate_overnight' => 'decimal:2',
        ];
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

        static::saved(fn () => PublicCache::flush());
        static::deleted(fn () => PublicCache::flush());
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true)->orderBy('sort_order');
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

    /**
     * Whether the given date falls inside the configured peak window.
     * Supports year-crossing windows (e.g. Dec 20 – Jan 5) by comparing only
     * the month/day components against the window's month/day boundaries.
     */
    public function isPeakDate(CarbonInterface $date): bool
    {
        if (! $this->hasPeakPricing()) {
            return false;
        }

        $dateMd = $date->format('md');
        $start = (int) $this->peak_start->format('md');
        $end = (int) $this->peak_end->format('md');

        if ($start <= $end) {
            return $dateMd >= $start && $dateMd <= $end;
        }

        // Window crosses the new year: in-window when after start OR before end.
        return $dateMd >= $start || $dateMd <= $end;
    }

    /**
     * Whether peak pricing is configured for this cottage.
     */
    public function hasPeakPricing(): bool
    {
        return $this->peak_start !== null
            && $this->peak_end !== null
            && ((float) ($this->peak_rate_daytour ?? 0) > 0 || (float) ($this->peak_rate_overnight ?? 0) > 0);
    }

    /**
     * Applicable rate for a date, applying the peak surcharge when the date
     * falls inside the peak window.
     */
    public function rateFor(CarbonInterface $date, string $type = Inquiry::TYPE_OVERNIGHT): string
    {
        if ($this->isPeakDate($date)) {
            $peak = $type === Inquiry::TYPE_DAY_TOUR ? $this->peak_rate_daytour : $this->peak_rate_overnight;
            if ($peak !== null && (float) $peak > 0) {
                return formatPrice($peak, 2, false);
            }
        }

        $base = $type === Inquiry::TYPE_DAY_TOUR ? $this->rate_daytour : $this->rate_overnight;

        return formatPrice($base, 2, false);
    }

    /**
     * Rate data payload for frontend JavaScript (peak-aware date pickers).
     * Used by BookingController, BookingPortalController, and Admin\InquiryController.
     */
    public static function ratesMap(?Collection $cottages = null): \Illuminate\Support\Collection
    {
        $cottages ??= static::all();

        return $cottages->mapWithKeys(fn ($c) => [
            $c->id => [
                'name' => $c->name,
                'capacity' => $c->capacity,
                Inquiry::TYPE_DAY_TOUR => (float) $c->rate_daytour,
                Inquiry::TYPE_OVERNIGHT => (float) $c->rate_overnight,
                'peak_'.Inquiry::TYPE_DAY_TOUR => $c->peak_rate_daytour !== null ? (float) $c->peak_rate_daytour : null,
                'peak_'.Inquiry::TYPE_OVERNIGHT => $c->peak_rate_overnight !== null ? (float) $c->peak_rate_overnight : null,
                'peak_start' => $c->peak_start?->format('m-d'),
                'peak_end' => $c->peak_end?->format('m-d'),
            ],
        ]);
    }
}
