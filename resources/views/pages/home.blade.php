@extends('layouts.app')

@section('title', 'Helena Beach Resort | Beachfront Cottages in Infanta, Quezon')
@section('description', 'Helena Beach Resort — beachfront cottages, fresh seafood, and unforgettable stays in Infanta, Quezon. Book your day tour or overnight getaway today.')

@push('head')
@if(App\Models\SiteSetting::getValue('hero_background'))
    <link rel="preload" as="image" href="{{ Storage::url(App\Models\SiteSetting::getValue('hero_background')) }}" fetchpriority="high">
@endif
@php
    $homeSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'LodgingBusiness',
        'name' => App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort'),
        'description' => App\Models\SiteSetting::getValue('site_description', 'Experience paradise in Infanta, Quezon.'),
        'url' => url('/'),
        'telephone' => App\Models\SiteSetting::getValue('contact_phone', ''),
        'email' => App\Models\SiteSetting::getValue('contact_email', ''),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => App\Models\SiteSetting::getValue('address', ''),
            'addressLocality' => 'Infanta',
            'addressRegion' => 'Quezon',
            'addressCountry' => 'PH',
        ],
    ];
    if ($avgRating) {
        $homeSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($avgRating, 1),
            'bestRating' => 5,
            'ratingCount' => $testimonials->count(),
        ];
    }
@endphp
<script type="application/ld+json">
@json($homeSchema)
</script>
@endpush

@section('content')
{{-- Hero --}}
<section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden">
    {{-- Background --}}
    @php $heroBg = App\Models\SiteSetting::getValue('hero_background'); @endphp
    <div class="absolute inset-0 bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800">
        @if($heroBg)
        <img src="{{ Storage::url($heroBg) }}" alt="" fetchpriority="high" class="absolute inset-0 w-full h-full object-cover opacity-25">
        <div class="absolute inset-0 bg-gradient-to-br from-teal-600/80 via-teal-700/70 to-cyan-800/80"></div>
        @endif
    </div>
    {{-- Decorative elements --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-[30rem] h-[30rem] bg-cyan-400/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-amber-400/5 rounded-full blur-3xl"></div>
    </div>
    <div class="relative z-10 text-center px-4 max-w-5xl mx-auto">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-teal-100 text-sm font-medium mb-8 backdrop-blur-sm">
            <x-icons name="sparkles" class="w-4 h-4" />
            {{ App\Models\SiteSetting::getValue('hero_tagline', 'Welcome to Paradise') }}
        </div>
        <h1 class="text-4xl sm:text-6xl md:text-7xl lg:text-8xl font-bold text-white mb-6 leading-[1.1] font-heading">
            <span class="text-amber-300">{{ App\Models\SiteSetting::getValue('hero_heading', 'Helena Beach Resort') }}</span>
        </h1>
        <p class="text-lg sm:text-xl md:text-2xl text-teal-100/90 mb-10 max-w-3xl mx-auto leading-relaxed">
            {{ App\Models\SiteSetting::getValue('hero_subtitle', 'Escape to paradise — unwind on pristine shores, enjoy beachfront cottages, and create unforgettable memories with family and friends in Infanta, Quezon.') }}
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('cottages.index') }}" class="inline-flex items-center px-8 py-4 bg-amber-400 text-amber-900 text-base font-semibold rounded-full hover:bg-amber-300 transition-all shadow-lg shadow-amber-400/30 hover:shadow-xl hover:shadow-amber-400/40 active:scale-95 group">
                <span>{{ App\Models\SiteSetting::getValue('hero_primary_btn_text', 'Explore Cottages') }}</span>
                <x-icons name="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-0.5 transition-transform" />
            </a>
            <a href="{{ route('book') }}" class="inline-flex items-center px-8 py-4 border-2 border-white/30 text-white text-base font-semibold rounded-full hover:bg-white/10 transition-all backdrop-blur-sm active:scale-95">
                {{ App\Models\SiteSetting::getValue('hero_secondary_btn_text', 'Book Now') }}
            </a>
        </div>
    </div>
    {{-- Wave Divider --}}
    <div class="absolute bottom-0 left-0 right-0 leading-none">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto" preserveAspectRatio="none">
            <path d="M0 40C240 80 480 100 720 80C960 60 1200 60 1440 80V120H0V40Z" fill="white" fill-opacity="0.1"/>
            <path d="M0 60C240 90 480 110 720 90C960 70 1200 70 1440 85V120H0V60Z" fill="white" fill-opacity="0.2"/>
            <path d="M0 80C240 100 480 120 720 100C960 80 1200 80 1440 95V120H0V80Z" fill="white"/>
        </svg>
    </div>
</section>

{{-- Featured Cottages --}}
@if($cottages->isNotEmpty())
<section class="py-20 sm:py-28 bg-white dark:bg-slate-800 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-700 dark:text-teal-300 mb-3">Accommodations</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4 font-heading">{{ App\Models\SiteSetting::getValue('section_cottages_heading', 'Our Cottages') }}</h2>
            <div class="w-16 h-1 bg-teal-500 rounded-full mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-slate-300 max-w-2xl mx-auto text-lg">{{ App\Models\SiteSetting::getValue('section_cottages_subtitle', 'Comfortable beachfront cottages perfect for your stay.') }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($cottages as $i => $cottage)
            <a href="{{ route('cottages.show', $cottage) }}" class="group bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 dark:border-slate-700 transition-all duration-300 reveal {{ $i > 0 ? 'reveal-delay-' . min($i, 4) : '' }}">
                <div class="aspect-[4/3] bg-teal-50 dark:bg-teal-900/30 overflow-hidden relative">
                    @if($cottage->primaryPhoto)
                    <img src="{{ Storage::url($cottage->primaryPhoto->photo_path) }}" alt="{{ $cottage->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-teal-300">
                        <x-icons name="building" class="w-16 h-16" />
                    </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-5">
                        <span class="text-white text-sm font-medium flex items-center gap-1">
                            <x-icons name="arrow-right" class="w-4 h-4" />
                            View Details
                        </span>
                    </div>
                </div>
                <div class="p-5">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-teal-700 dark:group-hover:text-teal-300 transition-colors">{{ $cottage->name }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-slate-400 line-clamp-2">{{ Str::limit(strip_tags($cottage->description), 100) }}</p>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
                            <x-icons name="users" class="w-4 h-4 text-gray-500 dark:text-slate-400" />
                            <span>{{ $cottage->capacity }}</span>
                        </div>
                        <div class="text-right">
                            @if($cottage->rate_daytour)
                            <div class="text-xs text-gray-500 dark:text-slate-400">Day Tour</div>
                            <div class="text-sm font-semibold text-teal-700 dark:text-teal-300">₱{{ number_format($cottage->rate_daytour) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-12 reveal">
            <a href="{{ route('cottages.index') }}" class="inline-flex items-center px-6 py-3 border-2 border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-300 font-medium rounded-full hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-colors group">
                <span>{{ App\Models\SiteSetting::getValue('section_cottages_btn_text', 'View All Cottages') }}</span>
                <x-icons name="arrow-right" class="ml-2 w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </a>
        </div>
    </div>
</section>
@endif

{{-- Gallery Preview --}}
@if($gallery->isNotEmpty())
<section class="py-20 sm:py-28 bg-gray-50 dark:bg-slate-800/50 relative">
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-0 right-0 w-96 h-96 bg-teal-400/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-700 dark:text-teal-300 mb-3">Moments</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4 font-heading">{{ App\Models\SiteSetting::getValue('section_gallery_heading', 'Gallery') }}</h2>
            <div class="w-16 h-1 bg-teal-500 rounded-full mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-slate-300 max-w-2xl mx-auto text-lg">{{ App\Models\SiteSetting::getValue('section_gallery_subtitle', 'A glimpse of the beauty that awaits you.') }}</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($gallery->take(8) as $i => $item)
            <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-slate-800 reveal {{ $i > 0 ? 'reveal-delay-' . min($i % 4 + 1, 4) : '' }}">
                <img src="{{ Storage::url($item->photo_path) }}" alt="{{ $item->title ?: 'Helena Beach Resort — gallery photo' }}" class="w-full h-full object-cover hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
                @if($item->title)
                <div class="absolute inset-0 bg-black/0 hover:bg-black/30 transition-colors flex items-end p-3 opacity-0 hover:opacity-100">
                    <p class="text-white text-xs font-medium">{{ $item->title }}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        <div class="text-center mt-12 reveal">
            <a href="{{ route('gallery.index') }}" class="inline-flex items-center px-6 py-3 border-2 border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-300 font-medium rounded-full hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-colors group">
                <span>{{ App\Models\SiteSetting::getValue('section_gallery_btn_text', 'View Full Gallery') }}</span>
                <x-icons name="arrow-right" class="ml-2 w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </a>
        </div>
    </div>
</section>
@endif

{{-- Testimonials --}}
@if($testimonials->isNotEmpty())
<section class="py-20 sm:py-28 bg-white dark:bg-slate-800 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-block text-xs font-semibold tracking-widest uppercase text-teal-700 dark:text-teal-300 mb-3">Testimonials</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4 font-heading">{{ App\Models\SiteSetting::getValue('section_reviews_heading', 'What Our Guests Say') }}</h2>
            <div class="w-16 h-1 bg-teal-500 rounded-full mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-slate-300 max-w-2xl mx-auto text-lg">{{ App\Models\SiteSetting::getValue('section_reviews_subtitle', 'Read what our visitors have to say about their stay at Helena Beach Resort.') }}</p>
        </div>

        @if($avgRating)
        <div class="flex items-center justify-center gap-3 mb-12 reveal">
            <div class="flex items-center gap-1">
                @for($i = 1; $i <= 5; $i++)
                    <x-icons name="star" class="w-6 h-6 {{ $i <= round($avgRating) ? 'text-amber-400' : 'text-gray-200' }}" />
                @endfor
            </div>
            <span class="text-xl font-bold text-gray-800 dark:text-slate-100">{{ number_format($avgRating, 1) }}</span>
            <span class="text-sm text-gray-500 dark:text-slate-400">average rating</span>
        </div>
        @endif

        {{-- Testimonial Carousel --}}
        <div x-data="testimonialCarousel()" class="relative max-w-5xl mx-auto reveal">
            <div class="overflow-hidden">
                <div class="flex transition-transform duration-500 ease-out"
                     :style="'transform: translateX(-' + (current * (100 / slidesPerView)) + '%)'">
                    @foreach($testimonials as $testimonial)
                    <div class="w-full md:w-1/2 lg:w-1/3 shrink-0 px-4">
                        <div class="bg-gray-50 dark:bg-slate-800/50 rounded-2xl p-6 sm:p-8 flex flex-col h-full">
                            <div class="flex items-center gap-1 mb-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <x-icons name="star" class="w-4 h-4 {{ $i <= $testimonial->rating ? 'text-amber-400' : 'text-gray-200' }}" />
                                @endfor
                            </div>
                            <p class="text-gray-600 dark:text-slate-300 text-sm leading-relaxed flex-1 italic">"{{ $testimonial->content }}"</p>
                            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-200 dark:border-slate-700">
                                @if($testimonial->guest_avatar)
                                <img src="{{ Storage::url($testimonial->guest_avatar) }}" alt="{{ $testimonial->guest_name }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-white dark:ring-slate-700">
                                @else
                                <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center text-teal-700 dark:text-teal-300 font-semibold text-sm ring-2 ring-white dark:ring-slate-700">
                                    {{ substr($testimonial->guest_name, 0, 1) }}
                                </div>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $testimonial->guest_name }}</p>
                                    @if($testimonial->cottage)
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Stayed at {{ $testimonial->cottage->name }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Carousel Controls --}}
            <div class="flex items-center justify-center gap-4 mt-8">
                <button @click="prev" aria-label="Previous testimonials" class="w-10 h-10 rounded-full border border-gray-200 dark:border-slate-700 flex items-center justify-center text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700/50 hover:text-teal-700 dark:hover:text-teal-300 transition-colors" :disabled="current === 0">
                    <x-icons name="chevron-left" class="w-5 h-5" />
                </button>
                <div class="flex items-center gap-2">
                    <template x-for="(_, i) in Array.from({length: totalPages})" :key="i">
                        <button @click="goTo(i)" :aria-label="'Go to slide ' + (i + 1)" :aria-current="i === current ? 'true' : 'false'" class="w-2 h-2 rounded-full transition-all duration-300"
                                :class="i === current ? 'bg-teal-700 w-6' : 'bg-gray-400 hover:bg-gray-500'"></button>
                    </template>
                </div>
                <button @click="next" aria-label="Next testimonials" class="w-10 h-10 rounded-full border border-gray-200 dark:border-slate-700 flex items-center justify-center text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700/50 hover:text-teal-700 dark:hover:text-teal-300 transition-colors" :disabled="current >= totalPages - 1">
                    <x-icons name="chevron-right" class="w-5 h-5" />
                </button>
            </div>
        </div>

        <div class="text-center mt-12 reveal">
            <a href="{{ route('reviews') }}" class="inline-flex items-center px-6 py-3 border-2 border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-300 font-medium rounded-full hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-colors group">
                <span>Read All Reviews</span>
                <x-icons name="arrow-right" class="ml-2 w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </a>
        </div>
    </div>
</section>

@push('scripts')
<script>
function testimonialCarousel() {
    return {
        current: 0,
        slidesPerView: 1,
        totalPages: {{ $testimonials->count() }},
        init() {
            this.updateSlidesPerView();
            window.addEventListener('resize', () => this.updateSlidesPerView());
        },
        updateSlidesPerView() {
            if (window.innerWidth >= 1024) this.slidesPerView = 3;
            else if (window.innerWidth >= 768) this.slidesPerView = 2;
            else this.slidesPerView = 1;
            this.totalPages = Math.max(0, Math.ceil({{ $testimonials->count() }} / this.slidesPerView) - 1);
            if (this.current > this.totalPages) this.current = this.totalPages;
        },
        next() { if (this.current < this.totalPages) this.current++; },
        prev() { if (this.current > 0) this.current--; },
        goTo(i) { this.current = i; },
    };
}
</script>
@endpush
@endif

{{-- CTA --}}
<section class="py-24 bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[40rem] h-[40rem] bg-amber-400/5 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <div class="reveal">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4 font-heading">{{ App\Models\SiteSetting::getValue('section_cta_heading', 'Ready for a Getaway?') }}</h2>
            <div class="w-16 h-1 bg-amber-400 rounded-full mx-auto mb-4"></div>
            <p class="text-teal-100/90 text-lg sm:text-xl mb-10 max-w-2xl mx-auto">{{ App\Models\SiteSetting::getValue('section_cta_subtitle', 'Contact us to book your stay or ask any questions.') }}</p>
            <a href="{{ route('contact') }}" class="inline-flex items-center px-8 py-4 bg-amber-400 text-amber-900 text-base font-semibold rounded-full hover:bg-amber-300 transition-all shadow-lg shadow-amber-400/30 hover:shadow-xl hover:shadow-amber-400/40 active:scale-95 group">
                <span>{{ App\Models\SiteSetting::getValue('section_cta_btn_text', 'Contact Us') }}</span>
                <x-icons name="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-0.5 transition-transform" />
            </a>
        </div>
    </div>
    {{-- Wave Divider Top --}}
    <div class="absolute top-0 left-0 right-0 leading-none rotate-180">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto" preserveAspectRatio="none">
            <path d="M0 20C240 60 480 60 720 40C960 20 1200 20 1440 40V60H0V20Z" fill="white" fill-opacity="0.05"/>
            <path d="M0 40C240 55 480 55 720 45C960 35 1200 35 1440 45V60H0V40Z" fill="white" fill-opacity="0.08"/>
        </svg>
    </div>
</section>
@endsection
