@extends('layouts.app')

@section('title', 'Find My Booking')
@section('description', 'Look up your booking at Helena Beach Resort using your email and reference code.')

@section('content')
<x-hero title="Find My Booking" subtitle="Enter your email and reference code to view your booking details." />

<section class="py-20 bg-white dark:bg-slate-800">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
            <x-icons name="x" class="w-4 h-4 shrink-0" />
            {{ $errors->first() ?: 'Please check the details below.' }}
        </div>
        @endif

        <div class="reveal">
            <form method="POST" action="{{ route('booking.portal.lookup') }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-8">
                @csrf

                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">
                        <x-icons name="email" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                        Email Address
                    </label>
                    <input type="email" name="email" id="email" required
                        value="{{ old('email') }}"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl text-sm focus:ring-2 focus:ring-teal-700 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none transition-all"
                        placeholder="you@example.com">
                </div>

                <div class="mb-6">
                    <label for="reference_code" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">
                        <x-icons name="tag" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                        Reference Code
                    </label>
                    <input type="text" name="reference_code" id="reference_code" required
                        value="{{ old('reference_code') }}"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl text-sm focus:ring-2 focus:ring-teal-700 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none font-mono transition-all"
                        placeholder="HB-000001">
                </div>

                <button type="submit"
                    class="w-full px-6 py-3 bg-teal-700 text-white font-medium rounded-xl hover:bg-teal-700 transition-all hover:shadow-lg hover:shadow-teal-600/20 active:scale-[0.98] inline-flex items-center justify-center gap-2">
                    <x-icons name="search" class="w-4 h-4" />
                    Find Booking
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    Don't have a booking yet?
                    <a href="{{ route('book') }}" class="text-teal-700 dark:text-teal-300 hover:text-teal-700 dark:hover:text-teal-300 font-medium">Book Now</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
