@extends('layouts.app')

@section('title', 'My Booking')
@section('description', 'View your booking details at Helena Beach Resort.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">My Booking</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Reference: <span class="font-mono font-semibold">{{ $inquiry->reference_code }}</span></p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            {{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Status</p>
                    @php
                        $statusColors = ['pending' => 'bg-yellow-50 text-yellow-700', 'confirmed' => 'bg-green-50 text-green-700', 'cancelled' => 'bg-red-50 text-red-700'];
                        $statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
                    @endphp
                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full {{ $statusColors[$inquiry->status] ?? 'bg-gray-50 text-gray-700' }}">
                        {{ $statusLabels[$inquiry->status] ?? ucfirst($inquiry->status) }}
                    </span>
                </div>
                @if($inquiry->total_amount)
                <div class="text-right">
                    <p class="text-sm text-gray-500 mb-1">Total</p>
                    <p class="text-xl font-bold text-teal-600">₱{{ number_format($inquiry->total_amount) }}</p>
                </div>
                @endif
            </div>

            <div class="space-y-5 text-sm">
                <h2 class="text-lg font-semibold text-gray-900">Booking Details</h2>

                <div class="grid grid-cols-2 gap-4">
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

                @if($inquiry->booking_type)
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-gray-500">Booking Type</p>
                    <p class="font-medium text-gray-900">{{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight' }}</p>
                </div>
                @endif

                @if($inquiry->check_in || $inquiry->check_out)
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
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
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-gray-500 mb-1">Message</p>
                    <p class="text-gray-700">{{ $inquiry->message }}</p>
                </div>
                @endif

                <div class="pt-4 border-t border-gray-100">
                    <p class="text-gray-500">Submitted</p>
                    <p class="font-medium text-gray-900">{{ $inquiry->created_at->format('M d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            @if($inquiry->status === 'confirmed')
            <a href="{{ route('invoice.show', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-white text-teal-600 font-medium rounded-xl border border-teal-200 hover:bg-teal-50 transition-colors">
                View Invoice
            </a>
            <a href="{{ route('invoice.download', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-white text-teal-600 font-medium rounded-xl border border-teal-200 hover:bg-teal-50 transition-colors">
                Download Invoice PDF
            </a>
            @endif

            @if($canCancel)
            <form method="POST" action="{{ route('booking.portal.cancel', $inquiry) }}"
                onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                @csrf
                <button type="submit"
                    class="w-full px-6 py-3 bg-white text-red-600 font-medium rounded-xl border border-red-200 hover:bg-red-50 transition-colors">
                    Cancel Booking
                </button>
            </form>
            @endif
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-sm text-teal-600 hover:text-teal-700">← Back to Home</a>
        </div>
    </div>
</section>
@endsection
