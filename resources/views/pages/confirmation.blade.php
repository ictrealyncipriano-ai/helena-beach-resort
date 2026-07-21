@extends('layouts.app')

@section('title', 'Booking Confirmation')
@section('description', 'Your booking inquiry has been received.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Inquiry Received!</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Thank you for your interest in Helena Beach Resort. We'll get back to you within 24 hours.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
            <div class="text-center mb-8 pb-8 border-b border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Reference Number</p>
                <p class="text-2xl font-bold text-teal-600 font-mono">{{ $inquiry->reference_code }}</p>
            </div>

            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900">Submitted Details</h2>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Name</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Email</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->email }}</p>
                    </div>
                    @if($inquiry->phone)
                    <div>
                        <p class="text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->phone }}</p>
                    </div>
                    @endif
                    @if($inquiry->cottage)
                    <div>
                        <p class="text-gray-500">Cottage</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->cottage->name }}</p>
                    </div>
                    @endif
                </div>

                @if($inquiry->check_in || $inquiry->check_out)
                <div class="grid grid-cols-2 gap-4 text-sm pt-4 border-t border-gray-100">
                    @if($inquiry->check_in)
                    <div>
                        <p class="text-gray-500">Check-in</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->check_in->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($inquiry->check_out)
                    <div>
                        <p class="text-gray-500">Check-out</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->check_out->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($inquiry->pax)
                    <div>
                        <p class="text-gray-500">Guests</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->pax }}</p>
                    </div>
                    @endif
                </div>
                @endif

                @if($inquiry->message)
                <div class="pt-4 border-t border-gray-100 text-sm">
                    <p class="text-gray-500 mb-1">Message</p>
                    <p class="text-gray-700">{{ $inquiry->message }}</p>
                </div>
                @endif

                <div class="pt-4 border-t border-gray-100 text-sm">
                    <p class="text-gray-500">Status</p>
                    <p class="inline-block mt-1 px-3 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-full">Pending</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('home') }}" class="text-sm text-teal-600 hover:text-teal-700">← Back to Home</a>
        </div>
    </div>
</section>
@endsection
