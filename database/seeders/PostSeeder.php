<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Seeds sample news/announcement posts.
 * Uses firstOrCreate so admin edits are preserved on re-seed.
 */
class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Welcome to Helena Beach Resort',
                'excerpt' => 'We are thrilled to open our doors in Infanta, Quezon — a quiet stretch of beachfront perfect for your next getaway.',
                'body' => "<p>Helena Beach Resort is now welcoming guests for day tours and overnight stays. Nestled along the pristine shores of Infanta, Quezon, we offer beachfront cottages, fresh seafood, and unforgettable memories.</p><h2>What to expect</h2><ul><li>Comfortable beachfront cottages</li><li>Fresh, locally sourced seafood</li><li>Safe, family-friendly beach</li></ul><p>Reserve your spot today — dates fill up quickly on weekends.</p>",
                'is_active' => true,
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Peak Season: Book Early',
                'excerpt' => 'Holiday and summer weekends fill fast. Secure your cottage now and save with our early-bird rates.',
                'body' => "<p>Peak season dates are going quickly. To guarantee your preferred cottage, we recommend booking at least two weeks ahead — especially for long weekends and holidays.</p><h2>Tips</h2><ol><li>Book online using the reservation form</li><li>Confirm your dates before weekends</li><li>Use the My Booking portal to manage your stay</li></ol>",
                'is_active' => true,
                'published_at' => now()->subDays(3),
            ],
        ];

        foreach ($posts as $post) {
            Post::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($post['title'])],
                $post
            );
        }
    }
}
