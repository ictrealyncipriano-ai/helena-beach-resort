<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds default site settings (brand info, hero text, contact details, etc.).
 * Uses firstOrCreate so admin modifications are preserved on re-seed.
 */
class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Helena Beach Resort', 'type' => 'text'],
            ['key' => 'site_description', 'value' => 'Experience paradise in Infanta, Quezon. Beachfront cottages, fresh seafood, and unforgettable memories.', 'type' => 'textarea'],
            ['key' => 'contact_email', 'value' => 'ict.realyncipriano@gmail.com', 'type' => 'text'],
            // TODO(client): replace this placeholder with the resort's real phone number.
            ['key' => 'contact_phone', 'value' => '0999 000 0000', 'type' => 'text'],
            ['key' => 'address', 'value' => 'Purok Buyan, Brgy. Dinahican, Infanta, Quezon', 'type' => 'textarea'],
            ['key' => 'operating_hours', 'value' => 'Monday - Sunday: 8:00 AM - 6:00 PM', 'type' => 'text'],
            // Hero section content
            ['key' => 'hero_tagline', 'value' => 'Welcome to', 'type' => 'text'],
            ['key' => 'hero_heading', 'value' => 'Helena Beach Resort', 'type' => 'text'],
            ['key' => 'hero_subtitle', 'value' => 'Escape to paradise — unwind on pristine shores, enjoy beachfront cottages, and create unforgettable memories.', 'type' => 'textarea'],
            ['key' => 'hero_primary_btn_text', 'value' => 'Explore Cottages', 'type' => 'text'],
            ['key' => 'hero_secondary_btn_text', 'value' => 'Book Now', 'type' => 'text'],
            // Section headings
            ['key' => 'section_cottages_heading', 'value' => 'Our Cottages', 'type' => 'text'],
            ['key' => 'section_cottages_subtitle', 'value' => 'Comfortable beachfront cottages perfect for your stay.', 'type' => 'textarea'],
            ['key' => 'section_cottages_btn_text', 'value' => 'View All Cottages', 'type' => 'text'],
            ['key' => 'section_gallery_heading', 'value' => 'Gallery', 'type' => 'text'],
            ['key' => 'section_gallery_subtitle', 'value' => 'A glimpse of the beauty that awaits you.', 'type' => 'textarea'],
            ['key' => 'section_gallery_btn_text', 'value' => 'View Full Gallery', 'type' => 'text'],
            ['key' => 'section_cta_heading', 'value' => 'Ready for a Getaway?', 'type' => 'text'],
            ['key' => 'section_cta_subtitle', 'value' => 'Contact us to book your stay or ask any questions.', 'type' => 'textarea'],
            ['key' => 'section_cta_btn_text', 'value' => 'Contact Us', 'type' => 'text'],
            ['key' => 'section_reviews_heading', 'value' => 'What Our Guests Say', 'type' => 'text'],
            ['key' => 'section_reviews_subtitle', 'value' => 'Read what our visitors have to say about their stay at Helena Beach Resort.', 'type' => 'textarea'],
            ['key' => 'facebook_url', 'value' => '#', 'type' => 'text'],
            ['key' => 'instagram_url', 'value' => 'https://www.instagram.com/helena_sa_infanta', 'type' => 'text'],
            ['key' => 'tiktok_url', 'value' => '#', 'type' => 'text'],
            ['key' => 'map_lat', 'value' => '14.702052118071348', 'type' => 'text'],
            ['key' => 'map_lng', 'value' => '121.72756162841773', 'type' => 'text'],
            // Legal / policy pages
            ['key' => 'legal_privacy', 'value' => 'Privacy policy content.', 'type' => 'textarea'],
            ['key' => 'legal_terms', 'value' => 'Terms and conditions content.', 'type' => 'textarea'],
            ['key' => 'legal_booking_policy', 'value' => 'Booking policy content.', 'type' => 'textarea'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
