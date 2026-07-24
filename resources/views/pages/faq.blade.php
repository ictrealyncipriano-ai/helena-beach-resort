@extends('layouts.app')

@section('title', 'Frequently Asked Questions')
@section('description', 'Find answers to common questions about Helena Beach Resort — reservations, rates, amenities, policies, and more.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Frequently Asked Questions</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Everything you need to know about your stay.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($faqs->isEmpty())
            <p class="text-center text-gray-500">No FAQs available at the moment. Please check back later.</p>
        @else
            <div class="space-y-3" x-data="{ open: null }">
                @foreach($faqs as $faq)
                    <div class="border border-teal-100 rounded-xl overflow-hidden">
                        <button
                            type="button"
                            class="w-full flex items-center justify-between px-6 py-4 text-left text-gray-900 font-medium hover:bg-teal-50 transition-colors"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                            :aria-expanded="open === {{ $loop->index }}"
                        >
                            <span>{{ $faq->question }}</span>
                            <svg
                                class="w-5 h-5 text-teal-600 shrink-0 ml-4 transition-transform duration-200"
                                :class="open === {{ $loop->index }} ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            class="px-6 pb-4 text-gray-600 leading-relaxed"
                            x-show="open === {{ $loop->index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                        >
                            {{ $faq->answer }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
