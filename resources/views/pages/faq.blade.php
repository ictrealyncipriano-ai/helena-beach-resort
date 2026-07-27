@extends('layouts.app')

@section('title', 'Frequently Asked Questions')
@section('description', 'Find answers to common questions about Helena Beach Resort — reservations, rates, amenities, policies, and more.')

@section('content')
{{-- Hero --}}
<section class="relative pt-32 pb-20 bg-gradient-to-br from-teal-600 via-teal-700 to-teal-800 overflow-hidden">
    {{-- Decorative blobs --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-[30rem] h-[30rem] bg-cyan-400/10 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-teal-100 text-sm font-medium mb-6 backdrop-blur-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Got questions? We've got answers.
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-4 font-heading">Frequently Asked Questions</h1>
        <p class="text-teal-100/90 text-lg sm:text-xl max-w-2xl mx-auto">Everything you need to know about your stay at Helena Beach Resort.</p>
    </div>
</section>

{{-- FAQ List --}}
<section class="py-20 bg-gradient-to-b from-white to-gray-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($faqs->isEmpty())
            {{-- Empty state --}}
            <div class="text-center py-20">
                <svg class="w-16 h-16 mx-auto text-teal-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No FAQs Available</h3>
                <p class="text-gray-500 max-w-md mx-auto">We haven't added any frequently asked questions yet. Check back later or reach out to us directly!</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-teal-600 text-white rounded-xl font-medium hover:bg-teal-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contact Us
                </a>
            </div>
        @else
            <div class="space-y-4" x-data="{ open: null }">
                @foreach($faqs as $faq)
                    <div
                        class="group bg-white rounded-2xl border border-gray-200/80 shadow-sm transition-all duration-200"
                        :class="open === {{ $loop->index }} ? 'shadow-md border-teal-200' : 'hover:shadow-md hover:border-gray-300'"
                    >
                        <button
                            type="button"
                            class="w-full flex items-center justify-between gap-4 px-6 sm:px-8 py-5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 rounded-2xl"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                            :aria-expanded="open === {{ $loop->index }}"
                            :class="open === {{ $loop->index }} ? 'pb-3' : ''"
                        >
                            <span class="flex items-start gap-4">
                                <span
                                    class="shrink-0 mt-0.5"
                                    :class="open === {{ $loop->index }} ? 'text-teal-600' : 'text-teal-400 group-hover:text-teal-500'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/>
                                    </svg>
                                </span>
                                <span
                                    class="text-base sm:text-lg font-semibold leading-snug transition-colors duration-200"
                                    :class="open === {{ $loop->index }} ? 'text-teal-900' : 'text-gray-800 group-hover:text-gray-900'"
                                >
                                    {{ $faq->question }}
                                </span>
                            </span>
                            <svg
                                class="w-5 h-5 shrink-0 transition-all duration-300 ease-out"
                                :class="open === {{ $loop->index }} ? 'rotate-180 text-teal-600' : 'text-gray-400 group-hover:text-gray-600'"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            class="px-6 sm:px-8 pb-6 text-gray-600 leading-relaxed"
                            x-show="open === {{ $loop->index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                        >
                            <div class="pl-9 border-l-2 border-teal-200">
                                <p class="text-[15px] sm:text-base leading-relaxed">
                                    {{ $faq->answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- Still have questions? --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-white rounded-3xl border border-gray-200/60 shadow-sm p-10 sm:p-14">
            <div class="w-14 h-14 bg-teal-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">Still have questions?</h2>
            <p class="text-gray-500 text-lg mb-8 max-w-md mx-auto">Can't find the answer you're looking for? We're happy to help.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a
                    href="{{ route('contact') }}"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-teal-600 text-white rounded-xl font-semibold hover:bg-teal-700 transition-colors shadow-sm hover:shadow-md"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Send us a message
                </a>
                <a
                    href="tel:{{ config('app.contact_phone', '#') }}"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-white text-gray-700 rounded-xl font-semibold border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-colors shadow-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Call us
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
