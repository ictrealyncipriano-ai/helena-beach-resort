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
            ['key' => 'hero_title', 'value' => 'Welcome to Helena Beach Resort', 'type' => 'text'],
            ['key' => 'hero_subtitle', 'value' => 'Escape to paradise — unwind on pristine shores, enjoy beachfront cottages, and create unforgettable memories.', 'type' => 'textarea'],
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
