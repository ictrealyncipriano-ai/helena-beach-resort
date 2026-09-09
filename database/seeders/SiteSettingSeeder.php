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
            ['key' => 'contact_email', 'value' => 'info@helena.labcoop.online', 'type' => 'text'],
            // NOTE(temp): replace with the resort's real inbox via the dashboard (super_admin) when available.
            ['key' => 'contact_phone', 'value' => '0912 345 6789', 'type' => 'text'],
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
            // Analytics / cookie consent
            ['key' => 'analytics_ga4_id', 'value' => '', 'type' => 'text'],
            ['key' => 'analytics_consent_enabled', 'value' => '1', 'type' => 'text'],
            ['key' => 'facebook_url', 'value' => '', 'type' => 'text'],
            ['key' => 'instagram_url', 'value' => 'https://www.instagram.com/helena_sa_infanta', 'type' => 'text'],
            ['key' => 'tiktok_url', 'value' => '', 'type' => 'text'],
            ['key' => 'map_lat', 'value' => '14.702052118071348', 'type' => 'text'],
            ['key' => 'map_lng', 'value' => '121.72756162841773', 'type' => 'text'],
            ['key' => 'map_embed_url', 'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.188516002515!2d121.72497447574254!3d14.701928774586516!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33982fd7700a733b%3A0x160b9c22db388372!2sHelena%20beach%20resort!5e0!3m2!1sen!2ssg!4v1786175999279!5m2!1sen!2ssg', 'type' => 'text'],
            // Legal / policy pages (NOTE(temp): draft copy — replace with final legal text via the dashboard).
            ['key' => 'legal_privacy', 'value' => 'Draft — our privacy policy is being finalized. Contact us at info@helena.labcoop.online for questions about how we handle your information.', 'type' => 'textarea'],
            ['key' => 'legal_terms', 'value' => 'Draft — our terms and conditions are being finalized. Contact us at info@helena.labcoop.online for questions about your stay.', 'type' => 'textarea'],
            ['key' => 'legal_booking_policy', 'value' => 'Draft — our booking policy is being finalized. Contact us at info@helena.labcoop.online for questions about reservations, payments, and cancellations.', 'type' => 'textarea'],
            // Brand assets (Storage paths; empty = fall back to shipped files).
            ['key' => 'site_logo', 'value' => '', 'type' => 'image'],
            ['key' => 'site_favicon', 'value' => '', 'type' => 'image'],
            ['key' => 'theme_color', 'value' => '#0f766e', 'type' => 'text'],
            // Geo / structured-data identity.
            ['key' => 'geo_region', 'value' => 'PH-QUE', 'type' => 'text'],
            ['key' => 'geo_placename', 'value' => 'Infanta, Quezon', 'type' => 'text'],
            ['key' => 'address_locality', 'value' => 'Infanta', 'type' => 'text'],
            ['key' => 'address_region', 'value' => 'Quezon', 'type' => 'text'],
            ['key' => 'address_country', 'value' => 'PH', 'type' => 'text'],
            // Booking rules (hours; invalid values fall back to 24 / 48).
            ['key' => 'booking_cutoff_hours', 'value' => '24', 'type' => 'text'],
            ['key' => 'booking_hold_hours', 'value' => '48', 'type' => 'text'],
            // About page story (rendered as sanitized HTML).
            ['key' => 'about_body', 'value' => '<p>Nestled along the pristine shores of Purok Buyan in Brgy. Dinahican, Infanta, Quezon, Helena Beach Resort offers a peaceful retreat surrounded by nature\'s beauty. Our resort is the perfect destination for families, couples, and groups looking to escape the hustle and bustle of city life.</p><p>With comfortable beachfront cottages, crystal-clear waters, and breathtaking sunsets, we provide an unforgettable tropical experience. Whether you are here for a day tour or an overnight stay, our dedicated team ensures your comfort and enjoyment.</p><p>At Helena Beach Resort, we take pride in offering genuine Filipino hospitality. From our friendly staff to our well-maintained facilities, every detail is designed to make your stay memorable.</p>', 'type' => 'textarea'],
            // Footer marketing line.
            ['key' => 'footer_tagline', 'value' => 'Experience the perfect getaway at Helena Beach Resort. Nestled along the pristine shores of Infanta, Quezon, we offer a peaceful retreat surrounded by nature.', 'type' => 'textarea'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
