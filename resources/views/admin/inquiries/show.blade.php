@extends('admin.layouts.app')

@section('title', 'Inquiry: ' . $inquiry->reference_code)
@section('header', 'Inquiry Details')
@section('description', 'Reference: ' . $inquiry->reference_code)

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.inquiries.index') }}" class="hover:text-teal-600 transition-colors dark:hover:text-teal-300">Inquiries</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">{{ $inquiry->reference_code }}</span>
    </nav>
@endsection

@section('content')
<div class="space-y-5">
    {{-- Guest Details --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Guest Details</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $inquiry->name }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Email</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">{{ $inquiry->email }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Phone</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">{{ $inquiry->phone ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Guest Profile --}}
    @if($inquiry->guest)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Guest Profile</h2>
            <a href="{{ route('admin.guests.show', $inquiry->guest) }}" class="text-xs font-medium text-teal-600 hover:text-teal-700 dark:text-teal-300 dark:hover:text-teal-200">View Profile</a>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-6">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $inquiry->guest->name }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Email</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">{{ $inquiry->guest->email }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Phone</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">{{ $inquiry->guest->phone ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Total Stays</span>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $inquiry->guest->total_stays }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Last Stay</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-200">{{ $inquiry->guest->last_stay_at?->format('M d, Y') ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Booking Details --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Booking Details</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 mb-5">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Type</span>
                    <p class="mt-1">@include('admin.components.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : 'Inquiry'])</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Check In</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Check Out</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Pax</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $inquiry->pax ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Cottage</span>
                    <p class="mt-1">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Total Amount</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">₱ {{ number_format($inquiry->total_amount, 2) }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Payment</span>
                    <p class="mt-1">
                        @if($inquiry->isRefunded())
                            @include('admin.components.badge', ['type' => 'danger', 'slot' => 'Refunded'])
                        @elseif($inquiry->isPaid())
                            @include('admin.components.badge', ['type' => 'success', 'slot' => 'Paid'])
                        @elseif($inquiry->hasFailedPayment())
                            @include('admin.components.badge', ['type' => 'danger', 'slot' => 'Payment Failed'])
                        @else
                            @include('admin.components.badge', ['type' => 'gray', 'slot' => 'Unpaid'])
                        @endif
                    </p>
                    @if($inquiry->isRefunded())
                        <p class="mt-1 text-xs text-red-500 dark:text-red-400">Refunded ₱{{ number_format($inquiry->refund_amount ?? $inquiry->paid_amount, 2) }} on {{ $inquiry->refunded_at?->format('M d, Y') }}</p>
                    @elseif($inquiry->isPaid())
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">₱{{ number_format($inquiry->paid_amount, 2) }} · {{ $inquiry->paymentMethodLabel() }} · {{ $inquiry->paid_at?->format('M d, Y') }}</p>
                    @elseif($inquiry->hasFailedPayment())
                        <p class="mt-1 text-xs text-red-500 dark:text-red-400">Last attempt failed {{ $inquiry->payment_failed_at?->format('M d, Y \a\t h:i A') }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Status</span>
                @include('admin.components.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : 'warning'), 'size' => 'md', 'slot' => ucfirst($inquiry->status)])
            </div>
        </div>
    </div>

    {{-- Message --}}
    @if($inquiry->message)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Message</h2>
        </div>
        <div class="p-5">
            <p class="text-sm text-gray-700 whitespace-pre-wrap dark:text-slate-200">{{ $inquiry->message }}</p>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.inquiries.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to List
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inquiries.edit', $inquiry) }}" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">Edit</a>
            @if($inquiry->status === 'confirmed' && ! $inquiry->isPaid())
                <button type="button"
                    @@click="$dispatch('open-confirm-mark-paid', { url: '{{ route('admin.inquiries.mark-paid', $inquiry) }}' })"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Mark as Paid</button>
            @endif
            @if($inquiry->isPaid() && ! $inquiry->isRefunded())
                <button type="button"
                    @@click="$dispatch('open-confirm-refund', { url: '{{ route('admin.inquiries.refund', $inquiry) }}' })"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">Refund</button>
            @endif
            @if($inquiry->status === 'pending')
                <button type="button"
                    @@click="$dispatch('open-confirm-confirm', { url: '{{ route('admin.inquiries.confirm', $inquiry) }}' })"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Confirm Booking</button>
                <button type="button"
                    @@click="$dispatch('open-confirm-cancel', { url: '{{ route('admin.inquiries.cancel', $inquiry) }}' })"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">Cancel Booking</button>
            @endif
        </div>
    </div>
</div>

@include('admin.components.confirm-dialog', ['name' => 'confirm', 'title' => 'Confirm Booking?', 'message' => 'Confirm this booking? This will create date blocks and send a confirmation email to the guest.', 'confirmText' => 'Confirm Booking', 'confirmClass' => 'bg-emerald-600 hover:bg-emerald-700 text-white'])
@include('admin.components.confirm-dialog', ['name' => 'cancel', 'title' => 'Cancel Booking?', 'message' => 'Cancel this booking? This will remove date blocks and send a cancellation email to the guest.', 'confirmText' => 'Cancel Booking', 'confirmClass' => 'bg-red-600 hover:bg-red-700 text-white'])
@include('admin.components.confirm-dialog', ['name' => 'mark-paid', 'title' => 'Mark as Paid?', 'message' => 'Mark this booking as paid (e.g. bank transfer or cash on site)?', 'confirmText' => 'Mark as Paid', 'confirmClass' => 'bg-emerald-600 hover:bg-emerald-700 text-white'])
@include('admin.components.confirm-dialog', ['name' => 'refund', 'title' => 'Refund Payment?', 'message' => 'Refund the full paid amount via PayMongo and cancel this booking? The guest will be notified by email.', 'confirmText' => 'Refund & Cancel', 'confirmClass' => 'bg-red-600 hover:bg-red-700 text-white'])
@endsection