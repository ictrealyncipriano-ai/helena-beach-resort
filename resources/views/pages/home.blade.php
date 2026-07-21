@extends('layouts.app')

@section('title', 'Home')

@section('content')
{{-- Hero --}}
<section class="relative min-h-[80vh] flex items-center justify-center bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800 overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.15\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">
        <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
            Welcome to<br>
            <span class="text-amber-300">Helena Beach Resort</span>
        </h1>
        <p class="text-lg sm:text-xl text-teal-100 mb-10 max-w-2xl mx-auto leading-relaxed">
            Escape to paradise — unwind on pristine shores, enjoy beachfront cottages, 
            and create unforgettable memories with family and friends in Infanta, Quezon.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('cottages.index') }}" class="inline-flex items-center px-8 py-3.5 bg-amber-400 text-amber-900 text-base font-semibold rounded-full hover:bg-amber-300 transition-all shadow-lg shadow-amber-400/30">
                Explore Cottages
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center px-8 py-3.5 border-2 border-white/30 text-white text-base font-semibold rounded-full hover:bg-white/10 transition-all">
                Book Now
            </a>
        </div>
    </div>
</section>

{{-- Featured Cottages --}}
@if($cottages->isNotEmpty())
<section class="py-16 sm:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Our Cottages</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Comfortable beachfront cottages perfect for your stay.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($cottages as $cottage)
            <a href="{{ route('cottages.show', $cottage) }}" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300">
                <div class="aspect-[4/3] bg-teal-50 overflow-hidden">
                    @if($cottage->primaryPhoto)
                    <img src="{{ Storage::url($cottage->primaryPhoto->photo_path) }}" alt="{{ $cottage->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-teal-300 text-6xl">🏠</div>
                    @endif
                </div>
                <div class="p-5">
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors">{{ $cottage->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($cottage->description), 100) }}</p>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span>👥 {{ $cottage->capacity }}</span>
                        </div>
                        <div class="text-right">
                            @if($cottage->rate_daytour)
                            <div class="text-xs text-gray-400">Day Tour</div>
                            <div class="text-sm font-semibold text-teal-600">₱{{ number_format($cottage->rate_daytour) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('cottages.index') }}" class="inline-flex items-center px-6 py-3 border-2 border-teal-600 text-teal-600 font-medium rounded-full hover:bg-teal-50 transition-colors">
                View All Cottages
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</section>
@endif

{{-- Gallery Preview --}}
@if($gallery->isNotEmpty())
<section class="py-16 sm:py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Gallery</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">A glimpse of the beauty that awaits you.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($gallery as $item)
            <div class="aspect-square rounded-xl overflow-hidden bg-gray-100">
                <img src="{{ Storage::url($item->photo_path) }}" alt="{{ $item->title }}" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500" loading="lazy">
            </div>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('gallery.index') }}" class="inline-flex items-center px-6 py-3 border-2 border-teal-600 text-teal-600 font-medium rounded-full hover:bg-teal-50 transition-colors">
                View Full Gallery
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section class="py-20 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Ready for a Getaway?</h2>
        <p class="text-teal-100 text-lg mb-8">Contact us to book your stay or ask any questions.</p>
        <a href="{{ route('contact') }}" class="inline-flex items-center px-8 py-3.5 bg-amber-400 text-amber-900 text-base font-semibold rounded-full hover:bg-amber-300 transition-all shadow-lg">
            Contact Us
            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>
</section>
@endsection
