<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'guest_name' => 'Maria Santos',
                'content' => 'We had an amazing time at Helena Beach Resort! The cottage was clean and comfortable, and the beachfront view was breathtaking. The staff were incredibly friendly and accommodating. Will definitely come back!',
                'rating' => 5,
                'sort_order' => 1,
            ],
            [
                'guest_name' => 'Juan dela Cruz',
                'content' => 'Perfect getaway from the city. The Kubo Aplaya cottage was cozy and authentic. We loved the fresh seafood and the serene atmosphere. Highly recommended for families!',
                'rating' => 5,
                'sort_order' => 2,
            ],
            [
                'guest_name' => 'Ana Reyes',
                'content' => 'Beautiful resort with well-maintained facilities. The staff went above and beyond to make our stay memorable. The sunset views from the beach are absolutely stunning.',
                'rating' => 4,
                'sort_order' => 3,
            ],
            [
                'guest_name' => 'Pedro Gonzales',
                'content' => 'Great place for a barkada outing! We rented Villa del Mar and it was perfect for our group. The karaoke machine was a hit. Only wish the WiFi was a bit faster.',
                'rating' => 4,
                'sort_order' => 4,
            ],
            [
                'guest_name' => 'Liza Mercado',
                'content' => 'Such a peaceful and relaxing experience. The Honeymoon Hideaway was perfect for me and my husband. The private outdoor shower was a unique touch we really enjoyed.',
                'rating' => 5,
                'sort_order' => 5,
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::firstOrCreate(
                ['guest_name' => $data['guest_name'], 'content' => $data['content']],
                $data
            );
        }
    }
}
