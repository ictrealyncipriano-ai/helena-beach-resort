<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What are your operating hours?',
                'answer' => 'We are open Monday to Sunday, 8:00 AM to 6:00 PM for day tours. Overnight stays are available upon reservation and check-in time is typically after 2:00 PM.',
                'sort_order' => 1,
            ],
            [
                'question' => 'How do I make a reservation?',
                'answer' => 'You can make a reservation by filling out the contact form on our website or by sending us a message on Facebook. We will get back to you to confirm availability and complete your booking.',
                'sort_order' => 2,
            ],
            [
                'question' => 'What are your cottage rates?',
                'answer' => 'Our cottage rates vary depending on the type of cottage and whether it is a day tour or overnight stay. Please visit our Cottages page for detailed pricing information on each cottage.',
                'sort_order' => 3,
            ],
            [
                'question' => 'Do you allow outside food and drinks?',
                'answer' => 'We kindly ask guests to enjoy our on-site food and beverage offerings. We take pride in serving fresh seafood and local dishes. For special dietary needs or large group events, please coordinate with us in advance.',
                'sort_order' => 4,
            ],
            [
                'question' => 'Is there parking available?',
                'answer' => 'Yes, we have parking space available for our guests. However, space may be limited during peak seasons and holidays, so early arrival is recommended.',
                'sort_order' => 5,
            ],
            [
                'question' => 'What amenities do the cottages have?',
                'answer' => 'Each cottage comes with different amenities. Common amenities include air conditioning, television, WiFi, kitchen access, and restrooms. Please check the specific cottage page for a complete list of amenities.',
                'sort_order' => 6,
            ],
            [
                'question' => 'What is your cancellation policy?',
                'answer' => 'Cancellations made at least 48 hours before your scheduled visit are fully refundable. For cancellations within 48 hours, a partial charge may apply. Please contact us directly for any changes to your reservation.',
                'sort_order' => 7,
            ],
            [
                'question' => 'Do you accept walk-in guests?',
                'answer' => 'Yes, we accept walk-in guests. However, we highly recommend making a reservation in advance, especially during weekends and peak season, to guarantee availability.',
                'sort_order' => 8,
            ],
            [
                'question' => 'Can I host events or parties at the resort?',
                'answer' => 'Yes, we welcome events and group gatherings! Please contact us in advance to discuss your requirements, including capacity, catering, and scheduling, so we can accommodate you properly.',
                'sort_order' => 9,
            ],
            [
                'question' => 'Are pets allowed in the resort?',
                'answer' => 'Please contact us directly regarding our pet policy, as it may vary depending on the cottage and season. We recommend inquiring before making a reservation.',
                'sort_order' => 10,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [...$faq, 'is_active' => true]
            );
        }
    }
}
