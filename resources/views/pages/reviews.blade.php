@extends('layouts.app')

@section('title', 'Guest Reviews')
@section('description', 'Read what our guests say about their stay at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">What Our Guests Say</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Read genuine reviews from our visitors.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($testimonials->isEmpty())
        <div class="text-center py-20">
            <div class="text-6xl mb-4">💬</div>
            <h2 class="text-xl font-semibold text-gray-600">No reviews yet</h2>
            <p class="text-gray-400 mt-2">Check back soon for guest testimonials.</p>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($testimonials as $testimonial)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col">
                <div class="flex items-center gap-1 mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="text-lg {{ $i <= $testimonial->rating ? 'text-amber-400' : 'text-gray-200' }}">★</span>
                    @endfor
                </div>
                <p class="text-gray-600 text-sm leading-relaxed flex-1">"{{ $testimonial->content }}"</p>
                <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100">
                    @if($testimonial->guest_avatar)
                    <img src="{{ Storage::url($testimonial->guest_avatar) }}" alt="{{ $testimonial->guest_name }}" class="w-10 h-10 rounded-full object-cover">
                    @else
                    <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 font-semibold text-sm">
                        {{ substr($testimonial->guest_name, 0, 1) }}
                    </div>
                    @endif
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $testimonial->guest_name }}</p>
                        @if($testimonial->cottage)
                        <p class="text-xs text-gray-400">Stayed at {{ $testimonial->cottage->name }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-10">
            {{ $testimonials->links() }}
        </div>
        @endif
    </div>
</section>
@endsection
