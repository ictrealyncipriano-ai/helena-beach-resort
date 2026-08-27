@extends('admin.layouts.app')

@section('title', 'Guest: ' . $guest->name)
@section('header', 'Guest Profile')
@section('description', $guest->name)

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.guests.index') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Guests</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">{{ $guest->name }}</span>
    </nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Guest Information</h2>
            <a href="{{ route('admin.guests.edit', $guest) }}" class="px-3 py-1.5 text-xs font-medium text-teal-700 bg-teal-50 rounded-lg hover:bg-teal-100 transition-colors dark:bg-teal-900/30 dark:text-teal-300 dark:hover:bg-teal-900/40">Edit</a>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $guest->name }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Email</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-300">{{ $guest->email }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Phone</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-300">{{ $guest->phone ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Total Stays</span>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $guest->total_stays }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Last Stay</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-300">{{ $guest->last_stay_at?->format('M d, Y') ?? '—' }}</p>
            </div>
        </div>

        @if($guest->inquiries->isNotEmpty())
            <div class="px-5 pb-5 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4 dark:border-slate-700">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Paid Bookings</span>
                    <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $paidCount }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Total Paid Amount</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice($paidAmount) }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Payment Failures</span>
                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">{{ $failedCount }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Refunded</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $refundedCount }}</p>
                </div>
            </div>
        @endif
        @if($guest->notes)
            <div class="px-5 pb-5">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Notes</span>
                <p class="mt-1 text-sm text-gray-700 dark:text-slate-300">{{ $guest->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Booking History --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Booking History</h2>
        </div>
        @if($guest->inquiries->isEmpty())
            <div class="p-5 text-sm text-gray-500 dark:text-slate-400 text-center">No bookings found for this guest.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Ref #</th>
                            <th class="text-left px-5 py-3 font-medium">Cottage</th>
                            <th class="text-left px-5 py-3 font-medium">Check In</th>
                            <th class="text-left px-5 py-3 font-medium">Check Out</th>
                            <th class="text-left px-5 py-3 font-medium">Type</th>
                            <th class="text-left px-5 py-3 font-medium">Status</th>
                            <th class="text-left px-5 py-3 font-medium">Payment</th>
                            <th class="text-right px-5 py-3 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($guest->inquiries as $inquiry)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 text-gray-500 font-medium dark:text-slate-400">{{ $inquiry->reference_code }}</td>
                            <td class="px-5 py-3 dark:text-slate-300">{{ $inquiry->cottage?->name ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-slate-300">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-slate-300">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3">@include('components.admin.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : 'Inquiry'])</td>
                            <td class="px-5 py-3">@include('components.admin.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : 'warning'), 'slot' => ucfirst($inquiry->status)])</td>
                            <td class="px-5 py-3">
                                @if($inquiry->isRefunded())
                                    @include('components.admin.badge', ['type' => 'danger', 'slot' => 'Refunded'])
                                @elseif($inquiry->isPaid())
                                    @include('components.admin.badge', ['type' => 'success', 'slot' => 'Paid'])
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-slate-400">{{ $inquiry->paymentMethodLabel() }}</p>
                                @elseif($inquiry->hasFailedPayment())
                                    @include('components.admin.badge', ['type' => 'danger', 'slot' => 'Payment Failed'])
                                @else
                                    @include('components.admin.badge', ['type' => 'gray', 'slot' => 'Unpaid'])
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-medium dark:text-white">{{ formatPrice($inquiry->total_amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div>
        <a href="{{ route('admin.guests.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to Guests
        </a>
    </div>
</div>
@endsection