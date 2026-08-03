@extends('layouts.app')

@section('title', 'My Booking')
@section('description', 'View your booking details at Helena Beach Resort.')

@section('content')
@section('og_title', 'My Booking — ' . $inquiry->reference_code)

<x-hero title="My Booking">
    <p class="text-teal-100/90 text-lg">Reference: <span class="font-mono font-semibold">{{ $inquiry->reference_code }}</span></p>
</x-hero>

<section class="py-20 bg-white">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2 reveal">
            <x-icons name="check" class="w-5 h-5 shrink-0" />
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2 reveal">
            <x-icons name="x" class="w-5 h-5 shrink-0" />
            {{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 reveal">
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100">
                <div>
                    <p class="text-sm text-gray-500 mb-2">Status</p>
                    @php
                        $statusColors = ['pending' => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200', 'confirmed' => 'bg-green-50 text-green-700 ring-1 ring-green-200', 'cancelled' => 'bg-red-50 text-red-700 ring-1 ring-red-200'];
                        $statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
                        $statusIcons = ['pending' => 'clock', 'confirmed' => 'check', 'cancelled' => 'x'];
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$inquiry->status] ?? 'bg-gray-50 text-gray-700' }}">
                        <x-icons name="{{ $statusIcons[$inquiry->status] ?? 'info' }}" class="w-3 h-3" />
                        {{ $statusLabels[$inquiry->status] ?? ucfirst($inquiry->status) }}
                    </span>
                    @if($inquiry->isPaid())
                    <span class="ml-2 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                        <x-icons name="check" class="w-3 h-3" />
                        Paid
                    </span>
                    @endif
                </div>
                @if($inquiry->total_amount)
                <div class="text-right">
                    <p class="text-sm text-gray-500 mb-1">Total</p>
                    <p class="text-2xl font-bold text-teal-600">₱{{ number_format($inquiry->total_amount) }}</p>
                    @if($inquiry->isPaid() && $inquiry->payment_method)
                    <p class="text-xs text-gray-400 mt-1">via {{ ucfirst($inquiry->payment_method) }} · {{ $inquiry->paid_at->format('M d, Y') }}</p>
                    @endif
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
                <div class="pt-4 border-t border-gray-100 flex items-center gap-2">
                    <x-icons name="{{ $inquiry->booking_type === 'day_tour' ? 'sun' : 'moon' }}" class="w-4 h-4 text-gray-400" />
                    <div>
                        <p class="text-gray-500">Booking Type</p>
                        <p class="font-medium text-gray-900">{{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight' }}</p>
                    </div>
                </div>
                @endif

                @if($inquiry->check_in || $inquiry->check_out)
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                    @if($inquiry->check_in)
                    <div>
                        <p class="text-gray-500">
                            <x-icons name="calendar" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" />
                            Check-in
                        </p>
                        <p class="font-medium text-gray-900">{{ $inquiry->check_in->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($inquiry->check_out)
                    <div>
                        <p class="text-gray-500">
                            <x-icons name="calendar" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" />
                            Check-out
                        </p>
                        <p class="font-medium text-gray-900">{{ $inquiry->check_out->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($inquiry->pax)
                    <div>
                        <p class="text-gray-500">
                            <x-icons name="users" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" />
                            Guests
                        </p>
                        <p class="font-medium text-gray-900">{{ $inquiry->pax }}</p>
                    </div>
                    @endif
                </div>
                @endif

                @if($inquiry->message)
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-gray-500 mb-1">Message</p>
                    <p class="text-gray-700 bg-gray-50 rounded-xl p-4">{{ $inquiry->message }}</p>
                </div>
                @endif

                <div class="pt-4 border-t border-gray-100">
                    <p class="text-gray-500">Submitted</p>
                    <p class="font-medium text-gray-900">{{ $inquiry->created_at->format('M d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-6 reveal">
            @if($inquiry->status === 'confirmed' && ! $inquiry->isPaid())
            <a href="{{ route('payment.pay', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-teal-600 text-white font-medium rounded-xl hover:bg-teal-700 transition-all inline-flex items-center justify-center gap-2">
                <x-icons name="card" class="w-4 h-4" />
                Pay Now — ₱{{ number_format($inquiry->total_amount) }}
            </a>
            @endif

            @if($inquiry->status === 'confirmed')
            <a href="{{ route('invoice.show', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-white text-teal-600 font-medium rounded-xl border border-teal-200 hover:bg-teal-50 transition-all hover:shadow-sm inline-flex items-center justify-center gap-2">
                <x-icons name="photo" class="w-4 h-4" />
                View Invoice
            </a>
            <a href="{{ route('invoice.download', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-white text-teal-600 font-medium rounded-xl border border-teal-200 hover:bg-teal-50 transition-all hover:shadow-sm inline-flex items-center justify-center gap-2">
                <x-icons name="download" class="w-4 h-4" />
                Download Invoice PDF
            </a>
            @endif

            @if($canCancel)
            <form method="POST" action="{{ route('booking.portal.cancel', $inquiry) }}"
                onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                @csrf
                <button type="submit"
                    class="w-full px-6 py-3 bg-white text-red-600 font-medium rounded-xl border border-red-200 hover:bg-red-50 transition-all inline-flex items-center justify-center gap-2">
                    <x-icons name="x" class="w-4 h-4" />
                    Cancel Booking
                </button>
            </form>
            @endif
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-sm text-teal-600 hover:text-teal-700 inline-flex items-center gap-1">
                <x-icons name="arrow-left" class="w-3 h-3" />
                Back to Home
            </a>
        </div>
    </div>
</section>
@endsection
