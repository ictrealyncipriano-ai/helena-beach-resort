<?php

namespace App\View\Composers;

use App\Models\SiteSetting;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Shares grouped site settings with public views so Blade templates
 * never query the SiteSetting model directly.
 *
 * Values (including every default) mirror the previous inline
 * SiteSetting::getValue() calls, so missing keys render exactly
 * as before. Emails, invoice PDFs and admin report layouts keep
 * direct model access deliberately (rendered off-request).
 */
class SiteSettingsComposer
{
    public function compose(View $view): void
    {
        $get = fn (string $key, mixed $default = null) => SiteSetting::getValue($key, $default);

        $socials = [];
        foreach (['facebook' => 'facebook_url', 'instagram' => 'instagram_url', 'tiktok' => 'tiktok_url'] as $icon => $key) {
            $url = (string) $get($key, '');
            $socials[$icon] = Str::startsWith($url, ['http://', 'https://']) ? $url : '';
        }

        $view->with([
            'site' => [
                'name' => $get('site_name', 'Helena Beach Resort'),
                'description' => $get('site_description', 'Experience paradise in Infanta, Quezon.'),
                'address' => $get('address', 'Purok Buyan, Brgy. Dinahican, Infanta, Quezon'),
                'contact_phone' => $get('contact_phone', ''),
                'contact_email' => $get('contact_email', 'ict.realyncipriano@gmail.com'),
                'og_image' => $get('og_image', asset('images/logo.jpg')),
            ],
            'socials' => $socials,
            'analytics' => [
                'ga4_id' => trim((string) $get('analytics_ga4_id', '')),
                'consent_required' => $get('analytics_consent_enabled', '1') === '1',
            ],
            'sections' => [
                'hero_background' => $get('hero_background'),
                'hero_tagline' => $get('hero_tagline', 'Welcome to Paradise'),
                'hero_heading' => $get('hero_heading', 'Helena Beach Resort'),
                'hero_subtitle' => $get('hero_subtitle', 'Escape to paradise — unwind on pristine shores, enjoy beachfront cottages, and create unforgettable memories with family and friends in Infanta, Quezon.'),
                'hero_primary_btn_text' => $get('hero_primary_btn_text', 'Explore Cottages'),
                'hero_secondary_btn_text' => $get('hero_secondary_btn_text', 'Book Now'),
                'section_cottages_heading' => $get('section_cottages_heading', 'Our Cottages'),
                'section_cottages_subtitle' => $get('section_cottages_subtitle', 'Comfortable beachfront cottages perfect for your stay.'),
                'section_cottages_btn_text' => $get('section_cottages_btn_text', 'View All Cottages'),
                'section_gallery_heading' => $get('section_gallery_heading', 'Gallery'),
                'section_gallery_subtitle' => $get('section_gallery_subtitle', 'A glimpse of the beauty that awaits you.'),
                'section_gallery_btn_text' => $get('section_gallery_btn_text', 'View Full Gallery'),
                'section_reviews_heading' => $get('section_reviews_heading', 'What Our Guests Say'),
                'section_reviews_subtitle' => $get('section_reviews_subtitle', 'Read what our visitors have to say about their stay at Helena Beach Resort.'),
                'section_cta_heading' => $get('section_cta_heading', 'Ready for a Getaway?'),
                'section_cta_subtitle' => $get('section_cta_subtitle', 'Book direct — no payment now, free cancellation. Check live availability in seconds.'),
                'section_cta_btn_text' => $get('section_cta_btn_text', 'Book Now'),
                'map_embed_url' => $get('map_embed_url', ''),
                'map_lat' => $get('map_lat', '14.702052118071348'),
                'map_lng' => $get('map_lng', '121.72756162841773'),
            ],
        ]);
    }
}
