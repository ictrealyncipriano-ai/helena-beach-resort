@extends('layouts.app')

@section('title', $cottage->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($cottage->description)), 150))

@if($cottage->primaryPhoto)
@section('og_image', Storage::url($cottage->primaryPhoto->photo_path))
@section('og_image_alt', $cottage->name)
@endif

@section('og_type', 'product')

@php
    $cottagePrice = $cottage->rate_overnight ?: $cottage->rate_daytour;
    $productSchema = null;
    if ($cottagePrice) {
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $cottage->name,
            'description' => \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($cottage->description)), 200),
            'image' => $cottage->primaryPhoto ? Storage::url($cottage->primaryPhoto->photo_path) : null,
            'url' => route('cottages.show', $cottage),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'PHP',
                'price' => $cottagePrice,
                'availability' => $cottage->is_available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ];
    }
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cottages', 'item' => route('cottages.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $cottage->name, 'item' => route('cottages.show', $cottage)],
        ],
    ];
@endphp
@push('head')
@if($productSchema)
<script type="application/ld+json">
@json($productSchema)
</script>
@endif
<script type="application/ld+json">
@json($breadcrumbSchema)
</script>
@endpush

@section('content')
<section class="relative pt-32 pb-12 overflow-hidden bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 reveal">
        <nav class="text-sm text-teal-200/80 mb-4" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
            <span class="mx-2 text-teal-200/50">›</span>
            <a href="{{ route('cottages.index') }}" class="hover:text-white transition-colors">Cottages</a>
            <span class="mx-2 text-teal-200/50">›</span>
            <span class="text-white font-medium">{{ $cottage->name }}</span>
        </nav>
        <a href="{{ route('cottages.index') }}" class="inline-flex items-center text-teal-200 hover:text-white text-sm mb-4 transition-colors group">
            <x-icons name="chevron-left" class="w-4 h-4 mr-1 group-hover:-translate-x-0.5 transition-transform" />
            Back to Cottages
        </a>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white font-heading">{{ $cottage->name }}</h1>
    </div>
    <div class="absolute bottom-0 left-0 right-0 leading-none">
        <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <path d="M0 20C240 35 480 35 720 25C960 15 1200 15 1440 25V40H0V20Z" fill="white" fill-opacity="0.08"/>
            <path d="M0 30C240 40 480 40 720 30C960 20 1200 20 1440 30V40H0V30Z" fill="white"/>
        </svg>
    </div>
</section>

<section class="py-8 sm:py-12 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-10">
                {{-- Photos --}}
                @if($cottage->photos->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 reveal">
                    @php $photoLabels = ['exterior view', 'interior view', 'bedroom', 'bathroom', 'amenities', 'beach view']; @endphp
                    @foreach($cottage->photos as $photo)
                    @php $photoAlt = $cottage->name . ' — ' . $photoLabels[$loop->index % count($photoLabels)]; @endphp
                    <button type="button" class="relative block w-full aspect-[4/3] rounded-xl overflow-hidden bg-teal-50 dark:bg-teal-900/30 cursor-pointer group"
                         onclick="openPhotoLightbox(this, '{{ Storage::url($photo->photo_path) }}', '{{ $photoAlt }}')"
                         aria-haspopup="dialog">
                        <img src="{{ Storage::url($photo->photo_path) }}" alt="{{ $photoAlt }}" width="600" height="450" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                        <span class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                            <x-icons name="search" class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                        </span>
                    </button>
                    @endforeach
                </div>
                @else
                <div class="aspect-video rounded-xl bg-gradient-to-br from-teal-100 to-teal-50 dark:from-teal-900/50 dark:to-teal-900/30 flex items-center justify-center text-teal-300/50">
                    <x-icons name="building" class="w-24 h-24" />
                </div>
                @endif

                {{-- Description --}}
                <div class="reveal">
                    <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-700 dark:text-teal-300 mb-3">Details</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 font-heading">About this Cottage</h2>
                    <div class="w-12 h-1 bg-teal-500 rounded-full mb-6"></div>
                    <div class="prose prose-teal max-w-none text-gray-600 dark:text-slate-300 leading-relaxed">
                        {!! $cottage->description !!}
                    </div>
                </div>

                {{-- Amenities --}}
                @if($cottage->amenities->isNotEmpty())
                <div class="reveal">
                    <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-700 dark:text-teal-300 mb-3">Features</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 font-heading">Amenities</h2>
                    <div class="w-12 h-1 bg-teal-500 rounded-full mb-6"></div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($cottage->amenities as $amenity)
                        <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 dark:bg-slate-800/50 rounded-xl text-sm text-gray-700 dark:text-slate-200 border border-gray-100 dark:border-slate-700 hover:border-teal-100 hover:bg-teal-50/50 transition-all">
                            <x-icons name="check" class="w-4 h-4 text-teal-500 shrink-0" />
                            <span>{{ $amenity->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6 reveal reveal-delay-1">
                    {{-- Pricing Card --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 space-y-6 hover:shadow-lg transition-shadow">
                        @if($cottage->rate_daytour)
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-slate-700">
                            <span class="text-gray-600 dark:text-slate-300 text-sm">
                                <x-icons name="sun" class="w-4 h-4 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                                Day Tour
                            </span>
                            <span class="text-xl font-bold text-teal-700 dark:text-teal-300">{{ formatPrice($cottage->rate_daytour) }}</span>
                        </div>
                        @endif
                        @if($cottage->rate_overnight)
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-slate-700">
                            <span class="text-gray-600 dark:text-slate-300 text-sm">
                                <x-icons name="moon" class="w-4 h-4 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                                Overnight
                            </span>
                            <span class="text-xl font-bold text-teal-700 dark:text-teal-300">{{ formatPrice($cottage->rate_overnight) }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-slate-700">
                            <span class="text-gray-600 dark:text-slate-300 text-sm">
                                <x-icons name="users" class="w-4 h-4 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                                Capacity
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">Up to {{ $cottage->capacity }} guests</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-slate-300 text-sm">Status</span>
                            @if($cottage->is_available)
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-3 py-1 rounded-full inline-flex items-center gap-1">
                                <x-icons name="check" class="w-3 h-3" />
                                Available
                            </span>
                            @else
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-3 py-1 rounded-full">Unavailable</span>
                            @endif
                        </div>
                        @if($cottage->is_available)
                        <a href="{{ route('book') }}?cottage_id={{ $cottage->id }}"
                           class="block w-full text-center px-6 py-3.5 min-h-[44px] flex items-center justify-center bg-teal-700 text-white font-semibold rounded-full hover:bg-teal-800 transition-all hover:shadow-lg hover:shadow-teal-600/20 active:scale-[0.98]">
                            Book This Cottage
                        </a>
                        <p class="text-xs text-gray-500 dark:text-slate-400 text-center">No payment now · Free cancellation · <a href="{{ route('booking-policy') }}" class="underline underline-offset-2 hover:text-teal-700">Booking Policy</a> · <a href="{{ route('contact') }}" class="underline underline-offset-2 hover:text-teal-700">Questions? Contact us</a></p>
                        @else
                        <p class="block w-full text-center px-6 py-3.5 min-h-[44px] flex items-center justify-center bg-gray-400 opacity-60 text-white font-semibold rounded-full cursor-not-allowed" aria-disabled="true">
                            Currently Unavailable
                        </p>
                        <a href="{{ route('contact') }}"
                           class="block w-full text-center px-6 py-3 min-h-[44px] flex items-center justify-center border-2 border-teal-600 text-teal-700 font-semibold rounded-full hover:bg-teal-50 transition-colors">
                            Contact Us
                        </a>
                        @endif
                    </div>

                    {{-- Availability Calendar --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm p-6 hover:shadow-lg transition-shadow">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-icons name="calendar" class="w-4 h-4 text-teal-700 dark:text-teal-300" />
                            Availability Calendar
                        </h3>
                        <div x-data="calendar(@js($blockedDates))">
                            <div class="flex items-center justify-between mb-3">
                                <button @click="prevMonth" aria-label="Previous month" class="min-w-[44px] min-h-[44px] p-3 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors text-gray-500 dark:text-slate-400">
                                    <x-icons name="chevron-left" class="w-4 h-4" />
                                </button>
                                <span class="text-sm font-semibold text-gray-700 dark:text-slate-200" x-text="monthLabel" aria-live="polite" role="status"></span>
                                <button @click="nextMonth" aria-label="Next month" class="min-w-[44px] min-h-[44px] p-3 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors text-gray-500 dark:text-slate-400">
                                    <x-icons name="chevron-right" class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-0 text-center mb-1" role="row">
                                <template x-for="day in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="day">
                                    <span role="columnheader" class="text-xs font-medium text-gray-500 dark:text-slate-400 py-1" x-text="day"></span>
                                </template>
                            </div>
                            <div class="grid grid-cols-7 gap-0" role="grid" aria-label="Availability calendar">
                                <template x-for="(day, i) in days" :key="i">
                                    <div class="text-sm py-1.5 rounded-lg text-center"
                                        :class="{
                                            'text-gray-300': !day,
                                            'text-gray-900 dark:text-slate-200': day && !day.blocked && !day.isPast,
                                            'text-red-400 dark:text-red-300 line-through': day && day.blocked,
                                            'text-gray-500': day && day.isPast && !day.blocked,
                                            'text-red-300 line-through': day && day.isPast && day.blocked
                                        }"
                                        :aria-label="day ? day.date + (day.blocked ? ' Booked' : ' Available') : null"
                                        :aria-disabled="day ? (day.blocked || day.isPast) : null"
                                        :title="day ? day.date + (day.blocked ? ' — Booked' : ' — Available') : null"
                                        x-text="day ? day.label : ''">
                                    </div>
                                </template>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-sm bg-red-100 dark:bg-red-900/40 border border-red-200 dark:border-red-800" aria-hidden="true"></span>
                                    Booked (struck through)
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-sm bg-green-100 dark:bg-green-900/40 border border-green-200 dark:border-green-800" aria-hidden="true"></span>
                                    Available
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-sm bg-gray-200 dark:bg-slate-700 border border-gray-300 dark:border-slate-600" aria-hidden="true"></span>
                                    Past
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Photo Lightbox --}}
<div id="photo-lightbox" role="dialog" aria-modal="true" aria-label="Cottage photo" tabindex="-1" class="fixed inset-0 z-50 bg-black/95 hidden items-center justify-center p-4" onclick="closePhotoLightbox(event)">
    <button onclick="closePhotoLightbox(event)" class="absolute top-4 right-4 w-12 h-12 flex items-center justify-center text-white/60 hover:text-white rounded-full hover:bg-white/10 transition-all z-30" aria-label="Close">
        <x-icons name="x" class="w-6 h-6" />
    </button>
    <img id="photo-lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-xl transition-all duration-300 shadow-2xl">
</div>

@push('scripts')
<script>
    let lastPhotoTrigger = null;
    function openPhotoLightbox(el, src, alt) {
        lastPhotoTrigger = el;
        const img = document.getElementById('photo-lightbox-img');
        img.src = src;
        img.alt = alt || '';
        const lb = document.getElementById('photo-lightbox');
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
        lb.focus({ preventScroll: true });
    }
    function closePhotoLightbox(e) {
        if (e.target === e.currentTarget || e.target.closest('button')) {
            document.getElementById('photo-lightbox').classList.add('hidden');
            document.getElementById('photo-lightbox').classList.remove('flex');
            document.body.style.overflow = '';
            if (lastPhotoTrigger) lastPhotoTrigger.focus({ preventScroll: true });
        }
    }
    document.addEventListener('keydown', function(e) {
        const lb = document.getElementById('photo-lightbox');
        if (lb.classList.contains('hidden')) return;
        if (e.key === 'Escape') {
            lb.classList.add('hidden');
            lb.classList.remove('flex');
            document.body.style.overflow = '';
            if (lastPhotoTrigger) lastPhotoTrigger.focus({ preventScroll: true });
        }
    });

    function calendar(blockedData) {
        // The Blade js directive now passes a real array literal; older
        // JSON-string payloads are still accepted so both render paths
        // produce the same structure.
        let blocked = blockedData;
        if (typeof blockedData === 'string') {
            try { blocked = JSON.parse(blockedData || '[]'); } catch { blocked = []; }
        }
        blocked = Array.isArray(blocked) ? blocked : [];

        const today = new Date();
        today.setHours(0,0,0,0);

        return {
            year: today.getFullYear(),
            month: today.getMonth(),

            get monthLabel() {
                return new Date(this.year, this.month).toLocaleString('default', { month: 'long', year: 'numeric' });
            },

            get days() {
                const firstDay = new Date(this.year, this.month, 1).getDay();
                const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                const grid = [];
                for (let i = 0; i < firstDay; i++) grid.push(null);
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateStr = `${this.year}-${String(this.month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const dateObj = new Date(this.year, this.month, d);
                    grid.push({
                        label: d,
                        date: dateStr,
                        blocked: blocked.includes(dateStr),
                        isPast: dateObj < today,
                    });
                }
                return grid;
            },

            prevMonth() {
                if (this.month === 0) { this.month = 11; this.year--; }
                else { this.month--; }
            },

            nextMonth() {
                if (this.month === 11) { this.month = 0; this.year++; }
                else { this.month++; }
            },
        };
    }
</script>
@endpush

@endsection
