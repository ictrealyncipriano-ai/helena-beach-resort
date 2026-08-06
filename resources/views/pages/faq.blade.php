@extends('layouts.app')

@section('title', 'Frequently Asked Questions')
@section('description', 'Find answers to common questions about Helena Beach Resort — reservations, rates, amenities, policies, and more.')

@section('content')
<x-hero title="Frequently Asked Questions"
         subtitle="Everything you need to know about your stay at Helena Beach Resort.">
    <x-slot:badge>
        <x-icons name="question" class="w-4 h-4" />
        Got questions? We've got answers.
    </x-slot:badge>
</x-hero>

{{-- FAQ List --}}
<section class="py-20 bg-gradient-to-b from-white to-gray-50 dark:from-slate-800 dark:to-slate-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($faqs->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 bg-teal-100 dark:bg-teal-900/40 rounded-2xl flex items-center justify-center mx-auto mb-6 text-teal-500 dark:text-teal-400">
                    <x-icons name="question" class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-semibold text-gray-800 dark:text-slate-100 mb-2">No FAQs Available</h3>
                <p class="text-gray-500 dark:text-slate-400 max-w-md mx-auto">We haven't added any frequently asked questions yet. Check back later or reach out to us directly!</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-teal-600 text-white rounded-xl font-medium hover:bg-teal-700 transition-colors shadow-sm">
                    <x-icons name="email" class="w-5 h-5" />
                    Contact Us
                </a>
            </div>
        @else
            <div class="space-y-4 reveal" x-data="{ open: null }">
                @foreach($faqs as $faq)
                    <div
                        class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200/80 dark:border-slate-700 shadow-sm transition-all duration-200"
                        :class="open === {{ $loop->index }} ? 'shadow-md border-teal-200' : 'hover:shadow-md hover:border-gray-300'"
                    >
                        <button
                            type="button"
                            class="w-full flex items-center justify-between gap-4 px-6 sm:px-8 py-5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 dark:focus-visible:ring-offset-slate-800 focus-visible:ring-offset-2 rounded-2xl"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                            :aria-expanded="open === {{ $loop->index }}"
                            :class="open === {{ $loop->index }} ? 'pb-3' : ''"
                        >
                            <span class="flex items-start gap-4">
                                <span
                                    class="shrink-0 mt-0.5"
                                    :class="open === {{ $loop->index }} ? 'text-teal-600' : 'text-teal-400 group-hover:text-teal-500'"
                                >
                                    <x-icons name="question" class="w-5 h-5" />
                                </span>
                                <span
                                    class="text-base sm:text-lg font-semibold leading-snug transition-colors duration-200"
                                    :class="open === {{ $loop->index }} ? 'text-teal-900' : 'text-gray-800 group-hover:text-gray-900'"
                                >
                                    {{ $faq->question }}
                                </span>
                            </span>
                            <x-icons name="chevron-down"
                                class="w-5 h-5 shrink-0 transition-all duration-300 ease-out"
                                ::class="open === {{ $loop->index }} ? 'rotate-180 text-teal-600' : 'text-gray-400 group-hover:text-gray-600'"
                            />
                        </button>
                        <div
                            class="px-6 sm:px-8 pb-6 text-gray-600 dark:text-slate-300 leading-relaxed"
                            x-show="open === {{ $loop->index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                        >
                            <div class="pl-9 border-l-2 border-teal-200 dark:border-teal-800">
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
<section class="py-20 bg-gray-50 dark:bg-slate-800/50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-gray-200/60 dark:border-slate-700 shadow-sm p-10 sm:p-14 reveal">
            <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <x-icons name="email" class="w-7 h-7 text-teal-600 dark:text-teal-300" />
            </div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-3 font-heading">Still have questions?</h2>
            <p class="text-gray-500 dark:text-slate-400 text-lg mb-8 max-w-md mx-auto">Can't find the answer you're looking for? We're happy to help.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a
                    href="{{ route('contact') }}"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-teal-600 text-white rounded-xl font-semibold hover:bg-teal-700 transition-all shadow-sm hover:shadow-md active:scale-95"
                >
                    <x-icons name="email" class="w-5 h-5" />
                    Send us a message
                </a>
                <a
                    href="tel:{{ config('app.contact_phone', '#') }}"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 rounded-xl font-semibold border border-gray-200 dark:border-slate-600 hover:border-gray-300 dark:hover:border-slate-500 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all shadow-sm active:scale-95"
                >
                    <x-icons name="phone" class="w-5 h-5" />
                    Call us
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
