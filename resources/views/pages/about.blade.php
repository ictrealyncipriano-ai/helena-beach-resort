@extends('layouts.app')

@section('title', 'About Us')
@section('description', 'Learn more about Helena Beach Resort in Infanta, Quezon.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">About Helena Beach Resort</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Discover your perfect beach getaway.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Story</h2>
                <div class="prose prose-teal max-w-none text-gray-600 space-y-4">
                    <p>Nestled along the pristine shores of Purok Buyan in Brgy. Dinahican, Infanta, Quezon, Helena Beach Resort offers a peaceful retreat surrounded by nature's beauty. Our resort is the perfect destination for families, couples, and groups looking to escape the hustle and bustle of city life.</p>
                    <p>With comfortable beachfront cottages, crystal-clear waters, and breathtaking sunsets, we provide an unforgettable tropical experience. Whether you're here for a day tour or an overnight stay, our dedicated team ensures your comfort and enjoyment.</p>
                    <p>At Helena Beach Resort, we take pride in offering genuine Filipino hospitality. From our friendly staff to our well-maintained facilities, every detail is designed to make your stay memorable.</p>
                </div>
            </div>
            <div class="aspect-[4/3] rounded-2xl overflow-hidden bg-teal-50 flex items-center justify-center text-teal-300 text-8xl">
                🏖️
            </div>
        </div>

        <div class="mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Our Location</h2>
            <div class="aspect-video rounded-2xl overflow-hidden bg-gray-100">
                <iframe
                    src="https://maps.google.com/maps?q=Purok+Buyan+Brgy+Dinahican+Infanta+Quezon&output=embed"
                    width="100%"
                    height="100%"
                    style="border:0; min-height: 400px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Helena Beach Resort Location">
                </iframe>
            </div>
            <p class="text-sm text-gray-500 mt-3 text-center">Purok Buyan, Brgy. Dinahican, Infanta, Quezon</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-teal-50 rounded-2xl p-6 text-center">
                <div class="text-4xl mb-3">📍</div>
                <h3 class="font-semibold text-gray-900 mb-2">Address</h3>
                <p class="text-sm text-gray-600">Purok Buyan, Brgy. Dinahican, Infanta, Quezon</p>
            </div>
            <div class="bg-teal-50 rounded-2xl p-6 text-center">
                <div class="text-4xl mb-3">⏰</div>
                <h3 class="font-semibold text-gray-900 mb-2">Operating Hours</h3>
                <p class="text-sm text-gray-600">Monday - Sunday: 8:00 AM - 6:00 PM</p>
                <p class="text-xs text-gray-400 mt-1">Overnight stays available upon reservation</p>
            </div>
            <div class="bg-teal-50 rounded-2xl p-6 text-center">
                <div class="text-4xl mb-3">📞</div>
                <h3 class="font-semibold text-gray-900 mb-2">Contact</h3>
                <p class="text-sm text-gray-600">Contact number available upon request</p>
                <a href="{{ route('contact') }}" class="inline-block mt-2 text-sm font-medium text-teal-600 hover:text-teal-700">Send us a message →</a>
            </div>
        </div>
    </div>
</section>
@endsection
