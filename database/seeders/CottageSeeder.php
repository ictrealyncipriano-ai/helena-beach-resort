<?php

namespace Database\Seeders;

use App\Models\Cottage;
use App\Models\CottageAmenity;
use Illuminate\Database\Seeder;

class CottageSeeder extends Seeder
{
    public function run(): void
    {
        $cottages = [
            [
                'name' => 'Kubo Aplaya',
                'slug' => 'kubo-aplaya',
                'description' => '<p>A traditional Filipino-inspired cottage right by the shore. Perfect for couples or small families who want a cozy beachfront experience. Made from natural materials with a nipa roof, this cottage offers an authentic tropical vibe.</p>',
                'capacity' => 4,
                'rate_daytour' => 1500,
                'rate_overnight' => 2500,
                'is_available' => true,
                'sort_order' => 1,
                'amenities' => ['Aircon', 'CR', 'Parking', 'Grill'],
            ],
            [
                'name' => 'Villa del Mar',
                'slug' => 'villa-del-mar',
                'description' => '<p>Our premium beachfront villa with modern amenities and stunning views of the sea. Features a spacious veranda perfect for sunset dinners. Ideal for families or groups looking for a luxurious beach getaway.</p>',
                'capacity' => 8,
                'rate_daytour' => 3500,
                'rate_overnight' => 5500,
                'is_available' => true,
                'sort_order' => 2,
                'amenities' => ['Aircon', 'TV', 'WiFi', 'Kitchen', 'CR', 'Parking', 'Karaoke', 'Grill'],
            ],
            [
                'name' => 'Casa del Sol',
                'slug' => 'casa-del-sol',
                'description' => '<p>A bright and airy cottage with large windows that let in the morning sun. Features an open-plan layout and a private garden area. Great for groups who want space and comfort.</p>',
                'capacity' => 6,
                'rate_daytour' => 2500,
                'rate_overnight' => 4000,
                'is_available' => true,
                'sort_order' => 3,
                'amenities' => ['Aircon', 'TV', 'WiFi', 'CR', 'Parking', 'Karaoke'],
            ],
            [
                'name' => 'Bahay Dalampasigan',
                'slug' => 'bahay-dalampasigan',
                'description' => '<p>Literally "Beach House" — this cottage sits directly on the sand with unobstructed views of the ocean. Fall asleep to the sound of waves and wake up to a breathtaking sunrise.</p>',
                'capacity' => 4,
                'rate_daytour' => 2000,
                'rate_overnight' => 3500,
                'is_available' => true,
                'sort_order' => 4,
                'amenities' => ['Aircon', 'CR', 'Grill'],
            ],
            [
                'name' => 'Pamilya Pavilion',
                'slug' => 'pamilya-pavilion',
                'description' => '<p>Our largest accommodation designed for big family gatherings and group events. Features a common hall, multiple sleeping areas, and a large outdoor dining space. Perfect for reunions and barkada outings.</p>',
                'capacity' => 12,
                'rate_daytour' => 5000,
                'rate_overnight' => 8000,
                'is_available' => true,
                'sort_order' => 5,
                'amenities' => ['Aircon', 'TV', 'WiFi', 'Kitchen', 'CR', 'Parking', 'Karaoke', 'Grill'],
            ],
            [
                'name' => 'Honeymoon Hideaway',
                'slug' => 'honeymoon-hideaway',
                'description' => '<p>An intimate cottage designed for couples. Features a private outdoor shower, a cozy loft sleeping area, and a small balcony with sea views. Romantic and secluded.</p>',
                'capacity' => 2,
                'rate_daytour' => 1800,
                'rate_overnight' => 3000,
                'is_available' => true,
                'sort_order' => 6,
                'amenities' => ['Aircon', 'CR', 'Parking'],
            ],
        ];

        foreach ($cottages as $data) {
            $amenityNames = $data['amenities'];
            unset($data['amenities']);

            $cottage = Cottage::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            foreach ($amenityNames as $name) {
                CottageAmenity::firstOrCreate([
                    'cottage_id' => $cottage->id,
                    'name' => $name,
                ]);
            }
        }
    }
}
