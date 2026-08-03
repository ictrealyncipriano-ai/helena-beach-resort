@extends('admin.layouts.app')

@section('title', 'Inquiry: ' . $inquiry->reference_code)
@section('header', 'Inquiry Details')
@section('description', 'Reference: ' . $inquiry->reference_code)

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.inquiries.index') }}" class="hover:text-teal-600 transition-colors">Inquiries</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">{{ $inquiry->reference_code }}</span>
    </nav>
@endsection

@section('content')
<div class="space-y-5">
    {{-- Guest Details --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Guest Details</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $inquiry->name }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Email</span>
                <p class="mt-1 text-sm text-gray-700">{{ $inquiry->email }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Phone</span>
                <p class="mt-1 text-sm text-gray-700">{{ $inquiry->phone ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Guest Profile --}}
    @if($inquiry->guest)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Guest Profile</h2>
            <a href="{{ route('admin.guests.show', $inquiry->guest) }}" class="text-xs font-medium text-teal-600 hover:text-teal-700">View Profile</a>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-6">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $inquiry->guest->name }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Email</span>
                <p class="mt-1 text-sm text-gray-700">{{ $inquiry->guest->email }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Phone</span>
                <p class="mt-1 text-sm text-gray-700">{{ $inquiry->guest->phone ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Stays</span>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $inquiry->guest->total_stays }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Last Stay</span>
                <p class="mt-1 text-sm text-gray-700">{{ $inquiry->guest->last_stay_at?->format('M d, Y') ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Booking Details --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Booking Details</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 mb-5">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Type</span>
                    <p class="mt-1">@include('admin.components.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : 'Inquiry'])</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Check In</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Check Out</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Pax</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $inquiry->pax ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Cottage</span>
                    <p class="mt-1">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Amount</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900">₱ {{ number_format($inquiry->total_amount, 2) }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Payment</span>
                    <p class="mt-1">@include('admin.components.badge', ['type' => $inquiry->isPaid() ? 'success' : 'gray', 'slot' => $inquiry->isPaid() ? 'Paid' : 'Unpaid'])</p>
                    @if($inquiry->isPaid())
                        <p class="mt-1 text-xs text-gray-500">{{ ucfirst($inquiry->payment_method ?? 'online') }} · {{ $inquiry->paid_at?->format('M d, Y') }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Status</span>
                @include('admin.components.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : 'warning'), 'size' => 'md', 'slot' => ucfirst($inquiry->status)])
            </div>
        </div>
    </div>

    {{-- Message --}}
    @if($inquiry->message)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Message</h2>
        </div>
        <div class="p-5">
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $inquiry->message }}</p>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.inquiries.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to List
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inquiries.edit', $inquiry) }}" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">Edit</a>
            @if($inquiry->status === 'confirmed' && ! $inquiry->isPaid())
                <form action="{{ route('admin.inquiries.mark-paid', $inquiry) }}" method="POST" class="inline" onsubmit="return confirm('Mark this booking as paid (e.g. bank transfer or cash on site)?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Mark as Paid</button>
                </form>
            @endif
            @if($inquiry->status === 'pending')
                <form action="{{ route('admin.inquiries.confirm', $inquiry) }}" method="POST" class="inline" onsubmit="return confirm('Confirm this booking? This will create date blocks and send a confirmation email.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Confirm Booking</button>
                </form>
                <form action="{{ route('admin.inquiries.cancel', $inquiry) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this booking? This will remove date blocks and send a cancellation email.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">Cancel Booking</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection