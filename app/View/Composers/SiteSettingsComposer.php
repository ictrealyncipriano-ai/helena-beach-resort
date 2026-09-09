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
                'name' => $get('site_name', config('app.name')),
                'description' => $get('site_description', 'A beachfront resort getaway.'),
                'address' => $get('address', ''),
                'contact_phone' => $get('contact_phone', ''),
                'contact_email' => $get('contact_email', 'info@example.com'),
                'operating_hours' => $get('operating_hours', ''),
                'footer_tagline' => $get('footer_tagline', 'A peaceful beachfront retreat.'),
                'og_image' => $get('og_image', SiteSetting::logoUrl()),
                'logo' => SiteSetting::logoUrl(),
                'favicon' => SiteSetting::faviconUrl(),
                'apple_touch_icon' => SiteSetting::appleTouchIconUrl(),
                'theme_color' => $get('theme_color', '#0f766e'),
                'geo_region' => $get('geo_region', 'PH-QUE'),
                'geo_placename' => $get('geo_placename', 'Infanta, Quezon'),
                'address_locality' => $get('address_locality', 'Infanta'),
                'address_region' => $get('address_region', 'Quezon'),
                'address_country' => $get('address_country', 'PH'),
            ],
            'socials' => $socials,
            'analytics' => [
                'ga4_id' => trim((string) $get('analytics_ga4_id', '')),
                'consent_required' => $get('analytics_consent_enabled', '1') === '1',
            ],
            'rules' => [
                'cutoff_hours' => SiteSetting::intValue('booking_cutoff_hours', 24, 1, 168),
                'hold_hours' => SiteSetting::intValue('booking_hold_hours', 48, 1, 168),
            ],
            'sections' => [
                'hero_background' => $get('hero_background'),
                'hero_tagline' => $get('hero_tagline', 'Welcome to Paradise'),
                'hero_heading' => $get('hero_heading', config('app.name')),
                'hero_subtitle' => $get('hero_subtitle', ''),
                'hero_primary_btn_text' => $get('hero_primary_btn_text', 'Explore Cottages'),
                'hero_secondary_btn_text' => $get('hero_secondary_btn_text', 'Book Now'),
                'section_cottages_heading' => $get('section_cottages_heading', 'Our Cottages'),
                'section_cottages_subtitle' => $get('section_cottages_subtitle', 'Comfortable beachfront cottages perfect for your stay.'),
                'section_cottages_btn_text' => $get('section_cottages_btn_text', 'View All Cottages'),
                'section_gallery_heading' => $get('section_gallery_heading', 'Gallery'),
                'section_gallery_subtitle' => $get('section_gallery_subtitle', 'A glimpse of the beauty that awaits you.'),
                'section_gallery_btn_text' => $get('section_gallery_btn_text', 'View Full Gallery'),
                'section_reviews_heading' => $get('section_reviews_heading', 'What Our Guests Say'),
                'section_reviews_subtitle' => $get('section_reviews_subtitle', 'Read what our visitors have to say about their stay.'),
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
