@extends('layouts.app')

@section('title', 'About Us')
@section('description', 'Learn more about Helena Beach Resort in Infanta, Quezon.')

@section('content')
<x-hero title="About Helena Beach Resort" subtitle="Discover your perfect beach getaway." />

<section class="py-20 sm:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center mb-20 reveal">
            <div>
                <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-600 mb-3">Our Story</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6 font-heading">Welcome to Helena Beach Resort</h2>
                <div class="w-12 h-1 bg-teal-500 rounded-full mb-6"></div>
                <div class="prose prose-teal max-w-none text-gray-600 space-y-4 leading-relaxed">
                    <p>Nestled along the pristine shores of Purok Buyan in Brgy. Dinahican, Infanta, Quezon, Helena Beach Resort offers a peaceful retreat surrounded by nature's beauty. Our resort is the perfect destination for families, couples, and groups looking to escape the hustle and bustle of city life.</p>
                    <p>With comfortable beachfront cottages, crystal-clear waters, and breathtaking sunsets, we provide an unforgettable tropical experience. Whether you're here for a day tour or an overnight stay, our dedicated team ensures your comfort and enjoyment.</p>
                    <p>At Helena Beach Resort, we take pride in offering genuine Filipino hospitality. From our friendly staff to our well-maintained facilities, every detail is designed to make your stay memorable.</p>
                </div>
            </div>
            <div class="aspect-[4/3] rounded-2xl overflow-hidden bg-gradient-to-br from-teal-100 to-teal-50 flex items-center justify-center text-teal-300/50">
                <x-icons name="building" class="w-24 h-24" />
            </div>
        </div>

        <div class="mb-20 reveal">
            <div class="text-center mb-10">
                <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-600 mb-3">Location</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 font-heading">Find Us</h2>
                <div class="w-12 h-1 bg-teal-500 rounded-full mx-auto"></div>
            </div>
            <div class="aspect-video rounded-2xl overflow-hidden shadow-lg border border-gray-100">
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
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 reveal">
            <div class="bg-gradient-to-br from-teal-50 to-teal-50/50 rounded-2xl p-8 text-center border border-teal-100/50 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mx-auto mb-4 text-teal-600">
                    <x-icons name="location" class="w-6 h-6" />
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Address</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Purok Buyan, Brgy. Dinahican, Infanta, Quezon</p>
            </div>
            <div class="bg-gradient-to-br from-teal-50 to-teal-50/50 rounded-2xl p-8 text-center border border-teal-100/50 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mx-auto mb-4 text-teal-600">
                    <x-icons name="clock" class="w-6 h-6" />
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Operating Hours</h3>
                <p class="text-sm text-gray-600">Monday - Sunday: 8:00 AM - 6:00 PM</p>
                <p class="text-xs text-gray-400 mt-1">Overnight stays available upon reservation</p>
            </div>
            <div class="bg-gradient-to-br from-teal-50 to-teal-50/50 rounded-2xl p-8 text-center border border-teal-100/50 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mx-auto mb-4 text-teal-600">
                    <x-icons name="phone" class="w-6 h-6" />
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Contact</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Contact number available upon request</p>
                <a href="{{ route('contact') }}" class="inline-block mt-3 text-sm font-medium text-teal-600 hover:text-teal-700 group">
                    Send us a message
                    <x-icons name="arrow-right" class="w-3 h-3 inline group-hover:translate-x-0.5 transition-transform" />
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
