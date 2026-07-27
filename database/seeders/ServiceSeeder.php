<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Beachfront Access', 'description' => 'Direct access to the pristine shores of Infanta, Quezon. Enjoy swimming, sunbathing, and stunning sunsets right at your doorstep.', 'icon' => '🏖️', 'category' => 'Amenities', 'sort_order' => 1],
            ['name' => 'On-site Parking', 'description' => 'Secure and convenient parking for all our guests. Ample space for cars and vans.', 'icon' => '🅿️', 'category' => 'Amenities', 'sort_order' => 2],
            ['name' => 'Airconditioned Cottages', 'description' => 'All our cottages are fully airconditioned for your comfort, ensuring a cool and relaxing stay regardless of the weather.', 'icon' => '❄️', 'category' => 'Amenities', 'sort_order' => 3],
            ['name' => 'Hot & Cold Showers', 'description' => 'Modern bathroom facilities with both hot and cold shower options in all cottages.', 'icon' => '🚿', 'category' => 'Amenities', 'sort_order' => 4],
            ['name' => 'Fresh Seafood Restaurant', 'description' => 'Enjoy freshly caught seafood prepared by our in-house chefs. We serve breakfast, lunch, and dinner with a variety of local delicacies.', 'icon' => '🍽️', 'category' => 'Dining', 'sort_order' => 5],
            ['name' => 'Grilling Areas', 'description' => 'Designated grilling areas where you can cook your own catch or enjoy a traditional Filipino barbecue with family and friends.', 'icon' => '🔥', 'category' => 'Dining', 'sort_order' => 6],
            ['name' => 'Karaoke', 'description' => 'Sing your heart out with our karaoke system available for guests. Perfect for parties and group gatherings.', 'icon' => '🎤', 'category' => 'Activities', 'sort_order' => 7],
            ['name' => 'Beach Volleyball', 'description' => 'A dedicated beach volleyball court for guests who want to stay active during their vacation.', 'icon' => '🏐', 'category' => 'Activities', 'sort_order' => 8],
            ['name' => 'Island Hopping', 'description' => 'Arrange island hopping tours to nearby islands and coves. Explore the natural beauty of Quezon province\'s coastal areas.', 'icon' => '🚤', 'category' => 'Activities', 'sort_order' => 9],
            ['name' => 'Event Pavilion', 'description' => 'A spacious covered pavilion perfect for family reunions, team building events, weddings, and other special occasions.', 'icon' => '🏛️', 'category' => 'Events', 'sort_order' => 10],
            ['name' => 'Team Building Area', 'description' => 'Open space and facilities designed for corporate team building activities and group workshops.', 'icon' => '🤝', 'category' => 'Events', 'sort_order' => 11],
        ];

        foreach ($services as $data) {
            Service::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
