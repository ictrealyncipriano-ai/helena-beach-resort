@extends('layouts.app')

@section('title', 'Find My Booking')
@section('description', 'Look up your booking at Helena Beach Resort using your email and reference code.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Find My Booking</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Enter your email and reference code to view your booking details.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            {{ $errors->first('reference_code') }}
        </div>
        @endif

        <form method="POST" action="{{ route('booking.portal.lookup') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
            @csrf

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" id="email" required
                    value="{{ old('email') }}"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
                    placeholder="you@example.com">
            </div>

            <div class="mb-6">
                <label for="reference_code" class="block text-sm font-medium text-gray-700 mb-1">Reference Code</label>
                <input type="text" name="reference_code" id="reference_code" required
                    value="{{ old('reference_code') }}"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none font-mono"
                    placeholder="HB-000001">
            </div>

            <button type="submit"
                class="w-full px-6 py-3 bg-teal-600 text-white font-medium rounded-xl hover:bg-teal-700 transition-colors">
                Find Booking
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-sm text-gray-500">
                Don't have a booking yet?
                <a href="{{ route('book') }}" class="text-teal-600 hover:text-teal-700 font-medium">Book Now</a>
            </p>
        </div>
    </div>
</section>
@endsection
