@extends('admin.layouts.app')

@section('header', 'Dashboard')
@section('description', 'Overview of your resort operations')

@section('content')
{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819"/></svg>
            </div>
            <span class="text-xs font-medium text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">Available: {{ $availableCottages }}</span>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $totalCottages }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Cottages</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
            </div>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-amber-600">{{ $pendingInquiries }}</p>
        <p class="text-sm text-gray-500 mt-1">Pending Inquiries</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ $confirmedThisMonth }}</p>
        <p class="text-sm text-gray-500 mt-1">Confirmed This Month</p>
        @if($revenueThisMonth)
            <p class="text-xs text-gray-400 mt-1">₱ {{ number_format($revenueThisMonth, 2) }} revenue</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            </div>
        </div>
        <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $upcomingCheckIns->count() }}</p>
        <p class="text-sm text-gray-500 mt-1">Upcoming Check-Ins</p>
    </div>
</div>

{{-- Tables Row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Upcoming Check-Ins --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Upcoming Check-Ins</h2>
            <a href="{{ route('admin.inquiries.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700">View all</a>
        </div>
        @if($upcomingCheckIns->isEmpty())
            @include('admin.components.empty-state', ['title' => 'No upcoming check-ins', 'message' => 'There are no confirmed bookings with upcoming check-in dates.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="text-left px-5 py-3 font-medium">Ref #</th>
                            <th class="text-left px-5 py-3 font-medium">Guest</th>
                            <th class="text-left px-5 py-3 font-medium">Cottage</th>
                            <th class="text-left px-5 py-3 font-medium">Check In</th>
                            <th class="text-left px-5 py-3 font-medium">Check Out</th>
                            <th class="text-center px-5 py-3 font-medium">Pax</th>
                            <th class="text-left px-5 py-3 font-medium">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($upcomingCheckIns as $inquiry)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 text-gray-500 font-medium">{{ $inquiry->reference_code }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $inquiry->name }}</td>
                            <td class="px-5 py-3">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</td>
                            <td class="px-5 py-3 text-gray-600">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">{{ $inquiry->pax ?? '—' }}</td>
                            <td class="px-5 py-3">@include('admin.components.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : 'Inquiry'])</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent Inquiries --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Recent Inquiries</h2>
            <a href="{{ route('admin.inquiries.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700">View all</a>
        </div>
        @if($recentInquiries->isEmpty())
            @include('admin.components.empty-state', ['title' => 'No inquiries yet', 'message' => 'Inquiries from guests will appear here.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="text-left px-5 py-3 font-medium">Name</th>
                            <th class="text-left px-5 py-3 font-medium">Email</th>
                            <th class="text-left px-5 py-3 font-medium">Cottage</th>
                            <th class="text-left px-5 py-3 font-medium">Status</th>
                            <th class="text-left px-5 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentInquiries as $inquiry)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $inquiry->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $inquiry->email }}</td>
                            <td class="px-5 py-3">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</td>
                            <td class="px-5 py-3">@include('admin.components.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : 'warning'), 'slot' => ucfirst($inquiry->status)])</td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ $inquiry->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Booking Type Distribution</h2>
        <div class="relative" style="max-height: 280px;">
            <canvas id="bookingTypeChart" x-data x-init="
                new Chart($el, {
                    type: 'doughnut',
                    data: {
                        labels: ['Day Tour', 'Overnight', 'Unspecified'],
                        datasets: [{
                            data: [{{ $bookingTypeData['day_tour'] ?? 0 }}, {{ $bookingTypeData['overnight'] ?? 0 }}, {{ $bookingTypeData[null] ?? 0 }}],
                            backgroundColor: ['#0d9488', '#f59e0b', '#e5e7eb'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' }
                            }
                        }
                    }
                })
            "></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Revenue (Last 6 Months)</h2>
        <div class="relative" style="max-height: 300px;">
            <canvas id="revenueChart" x-data x-init="
                new Chart($el, {
                    type: 'line',
                    data: {
                        labels: [@foreach($revenueData as $month => $total)'{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}',@endforeach],
                        datasets: [{
                            label: 'Revenue',
                            data: [{{ $revenueData->values()->implode(',') }}],
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13,148,136,0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#0d9488',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: function(v) { return '₱' + v.toLocaleString(); } } },
                            x: { grid: { display: false } }
                        }
                    }
                })
            "></canvas>
        </div>
    </div>
</div>

{{-- Popular Cottages --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-900">Popular Cottages (By Bookings)</h2>
    </div>
    @if($popularCottages->isEmpty())
        @include('admin.components.empty-state', ['title' => 'No cottage data', 'message' => 'Book some cottages to see popularity data.'])
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-5 py-3 font-medium">Cottage</th>
                        <th class="text-center px-5 py-3 font-medium">Total Bookings</th>
                        <th class="text-center px-5 py-3 font-medium">Max Pax</th>
                        <th class="text-right px-5 py-3 font-medium">Day Tour Rate</th>
                        <th class="text-right px-5 py-3 font-medium">Overnight Rate</th>
                        <th class="text-center px-5 py-3 font-medium">Available</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($popularCottages as $cottage)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $cottage->name }}</td>
                        <td class="px-5 py-3 text-center">@include('admin.components.badge', ['type' => 'primary', 'slot' => $cottage->inquiries_count])</td>
                        <td class="px-5 py-3 text-center text-gray-600">{{ $cottage->capacity }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">₱ {{ number_format($cottage->rate_daytour, 2) }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">₱ {{ number_format($cottage->rate_overnight, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            @if($cottage->is_available)
                                <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="w-5 h-5 text-red-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush