<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Helena Beach Resort', 'type' => 'text'],
            ['key' => 'site_description', 'value' => 'Experience paradise in Infanta, Quezon. Beachfront cottages, fresh seafood, and unforgettable memories.', 'type' => 'textarea'],
            ['key' => 'contact_email', 'value' => 'helenabeachresort@example.com', 'type' => 'text'],
            ['key' => 'contact_phone', 'value' => 'N/A', 'type' => 'text'],
            ['key' => 'address', 'value' => 'Purok Buyan, Brgy. Dinahican, Infanta, Quezon', 'type' => 'textarea'],
            ['key' => 'operating_hours', 'value' => 'Monday - Sunday: 8:00 AM - 6:00 PM', 'type' => 'text'],
            ['key' => 'hero_tagline', 'value' => 'Welcome to', 'type' => 'text'],
            ['key' => 'hero_heading', 'value' => 'Helena Beach Resort', 'type' => 'text'],
            ['key' => 'hero_subtitle', 'value' => 'Escape to paradise — unwind on pristine shores, enjoy beachfront cottages, and create unforgettable memories.', 'type' => 'textarea'],
            ['key' => 'hero_primary_btn_text', 'value' => 'Explore Cottages', 'type' => 'text'],
            ['key' => 'hero_secondary_btn_text', 'value' => 'Book Now', 'type' => 'text'],
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
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
