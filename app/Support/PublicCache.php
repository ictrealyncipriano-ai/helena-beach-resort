<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Central registry of cache keys for the public-facing pages.
 *
 * Content pages read slowly-changing records (cottages, gallery, testimonials,
 * FAQs, services) that only change through the admin panel, so their query
 * results are cached and flushed whenever one of those models is saved,
 * deleted, or restored. Booking/availability data (date blocks) is deliberately
 * kept out of the cache so guests always see real-time availability.
 */
class PublicCache
{
    public const HOME = 'pages.home';

    public const COTTAGES_INDEX = 'pages.cottages.index';

    public const FAQS_ALL = 'pages.faqs.all';

    public const SERVICES_ALL = 'pages.services.all';

    public const REVIEWS_ALL = 'pages.reviews.all';

    public const GALLERY_ALL = 'pages.gallery.all';

    public const GALLERY_CATEGORIES = 'pages.gallery.categories';

    public const SITEMAP = 'sitemap';

    /**
     * TTL for the aggregated homepage block. Short so un-cached edge cases
     * (first load, manual data fixes) stay reasonably fresh on their own.
     */
    public const HOME_TTL = 300;

    /**
     * TTL for the slower-changing content pages. The model events below flush
     * these immediately on admin edits, so this only covers out-of-band changes.
     */
    public const CONTENT_TTL = 600;

    /**
     * Drop every public-page cache entry. Called by model events whenever a
     * Cottage, CottagePhoto, Gallery, Testimonial, Faq, or Service is saved,
     * deleted, or restored (and by bulk actions that bypass model events).
     */
    public static function flush(): void
    {
        Cache::forget(self::HOME);
        Cache::forget(self::COTTAGES_INDEX);
        Cache::forget(self::FAQS_ALL);
        Cache::forget(self::SERVICES_ALL);
        Cache::forget(self::REVIEWS_ALL);
        Cache::forget(self::GALLERY_ALL);
        Cache::forget(self::GALLERY_CATEGORIES);
        Cache::forget(self::SITEMAP);
    }
}
