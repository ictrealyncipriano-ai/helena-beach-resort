@extends('layouts.app')

@section('title', 'Guest Reviews')
@section('description', 'Read what our guests say about their stay at Helena Beach Resort in Infanta, Quezon.')
@section('canonical', route('reviews'))

@push('head')
@if($testimonials->isNotEmpty())
@php
    $reviewsAvg = $testimonials->avg('rating');
    $reviewsSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'LodgingBusiness',
        'name' => 'Helena Beach Resort',
        'url' => route('reviews'),
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => round($reviewsAvg, 1),
            'bestRating' => 5,
            'ratingCount' => $testimonials->total(),
        ],
        'review' => $testimonials->map(fn ($t) => [
            '@type' => 'Review',
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $t->rating, 'bestRating' => 5],
            'author' => ['@type' => 'Person', 'name' => $t->guest_name],
            'reviewBody' => $t->content,
        ])->values(),
    ];
@endphp
<script type="application/ld+json">
@json($reviewsSchema)
</script>
@endif
@endpush

@section('content')
<x-hero title="What Our Guests Say" subtitle="Read genuine reviews from our visitors." />

<section class="py-20 sm:py-28 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($testimonials->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-6 text-gray-500 dark:text-slate-400">
                <x-icons name="heart" class="w-8 h-8" />
            </div>
            <h2 class="text-xl font-semibold text-gray-600 dark:text-slate-300">No reviews yet</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-2">Check back soon for guest testimonials.</p>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($testimonials as $i => $testimonial)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 sm:p-8 flex flex-col hover:shadow-lg hover:border-teal-100 transition-all duration-300 reveal {{ $i > 0 ? 'reveal-delay-' . min($i % 3 + 1, 4) : '' }}">
                <div class="flex items-center gap-1 mb-4" role="img" aria-label="Rated {{ $testimonial->rating }} out of 5">
                    @for($star = 1; $star <= 5; $star++)
                        <x-icons name="star" class="w-4 h-4 {{ $star <= $testimonial->rating ? 'text-amber-400' : 'text-gray-200' }}" />
                    @endfor
                </div>
                <p class="text-gray-600 dark:text-slate-300 text-sm leading-relaxed flex-1 italic">"{{ $testimonial->content }}"</p>
                <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-slate-700">
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
            @endforeach
        </div>
        <div class="mt-12 reveal">
            {{ $testimonials->links() }}
        </div>
        @endif
    </div>
</section>
@endsection
