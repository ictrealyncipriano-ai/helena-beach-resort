@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('description', 'Overview of your resort operations')

@section('content')
<script>
    window.siteThemeColor = function() {
        return getComputedStyle(document.documentElement).getPropertyValue('--color-teal-600').trim() || '#0d9488';
    };
</script>
<div class="space-y-6 sm:space-y-8">

    {{-- Stat Cards --}}
    <div
        x-data="{
            visible: false,
            init() {
                const observer = new IntersectionObserver(([entry]) => {
                    if (entry.isIntersecting) { this.visible = true; observer.disconnect(); }
                }, { threshold: 0.1 });
                observer.observe(this.$el);
            }
        }"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6"
    >
        {{-- Total Cottages --}}
        <div
            x-data="{ count: 0, target: {{ $totalCottages }}, animate() { let t=0; const s=Math.max(1,Math.floor(this.target/30)); const i=setInterval(() => { t+=s; if(t>=this.target){ t=this.target; clearInterval(i); } this.count=t; }, 30); } }"
            x-init="$watch('$parent.visible', v => { if(v) animate() })"
            class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-300 stat-card-glow dark:bg-slate-800 dark:border-slate-700"
        >
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-teal"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl stat-icon-teal flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-teal-700 dark:text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819"/></svg>
                </div>
                <span class="text-[11px] font-semibold text-teal-700 bg-teal-50/80 px-2.5 py-1 rounded-full border border-teal-100/50 dark:text-teal-300 dark:bg-teal-900/30 dark:border-teal-900/50">Available: {{ $availableCottages }}</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight dark:text-white" x-text="count"></p>
            <p class="text-sm text-gray-500 mt-1.5 font-medium dark:text-slate-400">Total Cottages</p>
        </div>

        {{-- Pending Inquiries --}}
        <div
            x-data="{ count: 0, target: {{ $pendingInquiries }}, animate() { let t=0; const s=Math.max(1,Math.floor(this.target/30)); const i=setInterval(() => { t+=s; if(t>=this.target){ t=this.target; clearInterval(i); } this.count=t; }, 30); } }"
            x-init="$watch('$parent.visible', v => { if(v) animate() })"
            class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-300 stat-card-amber dark:bg-slate-800 dark:border-slate-700"
        >
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-amber"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl stat-icon-amber flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight dark:text-white" x-text="count"></p>
            <p class="text-sm text-gray-500 mt-1.5 font-medium dark:text-slate-400">Pending Inquiries</p>
        </div>

        {{-- Confirmed This Month --}}
        <div
            x-data="{ count: 0, target: {{ $confirmedThisMonth }}, animate() { let t=0; const s=Math.max(1,Math.floor(this.target/30)); const i=setInterval(() => { t+=s; if(t>=this.target){ t=this.target; clearInterval(i); } this.count=t; }, 30); } }"
            x-init="$watch('$parent.visible', v => { if(v) animate() })"
class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-300 stat-card-emerald dark:bg-slate-800 dark:border-slate-700"
        >
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-emerald"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl stat-icon-emerald flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight dark:text-white" x-text="count"></p>
            <p class="text-sm text-gray-500 mt-1.5 font-medium dark:text-slate-400">Confirmed This Month</p>
            @if($revenueThisMonth)
                <p class="text-xs text-emerald-600 font-medium mt-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c0 0 .5.159 1.121.159 1.621 0 2.5-1.1 2.5-2.5 0-1.4-.879-2.5-2.5-2.5-.621 0-1.121.159-1.121.159l-.879.659M3 13.125V5.625A2.625 2.625 0 015.625 3h12.75A2.625 2.625 0 0121 5.625v7.5A2.625 2.625 0 0118.375 15.75h-5.25L7.5 21v-5.25H5.625A2.625 2.625 0 013 13.125z"/></svg>
                    ₱ {{ number_format($revenueThisMonth, 2) }} collected · {{ $paidThisMonth }} paid
                </p>
            @endif
        </div>

        {{-- Upcoming Check-Ins --}}
        <div
            x-data="{ count: 0, target: {{ $upcomingCheckIns->count() }}, animate() { let t=0; const s=Math.max(1,Math.floor(this.target/30)); const i=setInterval(() => { t+=s; if(t>=this.target){ t=this.target; clearInterval(i); } this.count=t; }, 30); } }"
            x-init="$watch('$parent.visible', v => { if(v) animate() })"
            class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-300 stat-card-gray dark:bg-slate-800 dark:border-slate-700"
        >
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-gray"></div>
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl stat-icon-gray flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-gray-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight dark:text-white" x-text="count"></p>
            <p class="text-sm text-gray-500 mt-1.5 font-medium dark:text-slate-400">Upcoming Check-Ins</p>
        </div>
    </div>

    {{-- Tables Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Upcoming Check-Ins --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 dark:text-white">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    Upcoming Check-Ins
                </h2>
                <a href="{{ route('admin.inquiries.index') }}" class="text-xs font-medium text-teal-700 hover:text-teal-700 transition-colors flex items-center gap-1 dark:text-teal-300 dark:hover:text-teal-200">
                    View all
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>
            @if($upcomingCheckIns->isEmpty())
                @include('admin.components.empty-state', ['title' => 'No upcoming check-ins', 'message' => 'There are no confirmed bookings with upcoming check-in dates.'])
            @else
                {{-- Desktop table --}}
                <div class="desktop-table overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase tracking-wider dark:text-slate-400">
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Ref #</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Guest</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Cottage</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Check In</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Check Out</th>
                                <th class="text-center px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Pax</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                            @foreach($upcomingCheckIns as $inquiry)
                            <tr class="hover:bg-gray-50/50 transition-colors dark:hover:bg-slate-700/40">
                                <td class="px-5 py-3.5 text-gray-500 font-mono text-xs font-medium dark:text-slate-400">{{ $inquiry->reference_code }}</td>
                                <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white">{{ $inquiry->name }}</td>
                                <td class="px-5 py-3.5">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</td>
                                <td class="px-5 py-3.5 text-gray-600 dark:text-slate-300">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-gray-600 dark:text-slate-300">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center text-gray-600 dark:text-slate-300">{{ $inquiry->pax ?? '—' }}</td>
                                <td class="px-5 py-3.5">@include('admin.components.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : 'Inquiry'])</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="mobile-table-cards p-4 space-y-3">
                    @foreach($upcomingCheckIns as $inquiry)
                    <div class="mobile-table-card">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $inquiry->name }}</span>
                            @include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])
                        </div>
                        <div class="field">
                            <span class="field-label">Ref #</span>
                            <span class="field-value font-mono text-xs">{{ $inquiry->reference_code }}</span>
                        </div>
                        <div class="field">
                            <span class="field-label">Check In</span>
                            <span class="field-value">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</span>
                        </div>
                        <div class="field">
                            <span class="field-label">Check Out</span>
                            <span class="field-value">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-gray-500 dark:text-slate-400">{{ $inquiry->pax ?? '—' }} guest(s)</span>
                            @include('admin.components.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : 'Inquiry'])
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent Inquiries --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 dark:text-white">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Recent Inquiries
                </h2>
                <a href="{{ route('admin.inquiries.index') }}" class="text-xs font-medium text-teal-700 hover:text-teal-700 transition-colors flex items-center gap-1 dark:text-teal-300 dark:hover:text-teal-200">
                    View all
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>
            @if($recentInquiries->isEmpty())
                @include('admin.components.empty-state', ['title' => 'No inquiries yet', 'message' => 'Inquiries from guests will appear here.'])
            @else
                {{-- Desktop table --}}
                <div class="desktop-table overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase tracking-wider dark:text-slate-400">
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Name</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Email</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Cottage</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Status</th>
                                <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                            @foreach($recentInquiries as $inquiry)
                            <tr class="hover:bg-gray-50/50 transition-colors dark:hover:bg-slate-700/40">
                                <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white">{{ $inquiry->name }}</td>
                                <td class="px-5 py-3.5 text-gray-500 dark:text-slate-400">{{ $inquiry->email }}</td>
                                <td class="px-5 py-3.5">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $statusDot = match($inquiry->status) {
                                            'confirmed' => 'bg-emerald-500',
                                            'cancelled' => 'bg-red-500',
                                            default => 'bg-amber-500',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                                        @include('admin.components.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : 'warning'), 'slot' => ucfirst($inquiry->status)])
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500 text-xs dark:text-slate-400">{{ $inquiry->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="mobile-table-cards p-4 space-y-3">
                    @foreach($recentInquiries as $inquiry)
                    <div class="mobile-table-card">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $inquiry->name }}</span>
                            @php
                                $statusDot = match($inquiry->status) {
                                    'confirmed' => 'bg-emerald-500',
                                    'cancelled' => 'bg-red-500',
                                    default => 'bg-amber-500',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                                @include('admin.components.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : 'warning'), 'slot' => ucfirst($inquiry->status)])
                            </span>
                        </div>
                        <div class="field">
                            <span class="field-label">Email</span>
                            <span class="field-value text-gray-500 text-xs dark:text-slate-400">{{ $inquiry->email }}</span>
                        </div>
                        <div class="field">
                            <span class="field-label">Cottage</span>
                            <span class="field-value">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</span>
                        </div>
                        <div class="field">
                            <span class="field-label">Date</span>
                            <span class="field-value text-xs text-gray-500 dark:text-slate-400">{{ $inquiry->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 dark:text-white">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    Booking Type Distribution
                </h2>
                <span class="text-xs text-gray-500 font-medium dark:text-slate-400">{{ $bookingTypeData->sum() }} total</span>
            </div>
            <div class="relative chart-container flex justify-center" style="max-height: 280px;">
                <canvas id="bookingTypeChart" role="img" aria-label="Booking type distribution doughnut chart" x-data x-init="
                    new Chart($el, {
                        type: 'doughnut',
                        data: {
                            labels: ['Day Tour', 'Overnight', 'Unspecified'],
                            datasets: [{
                                data: [{{ $bookingTypeData['day_tour'] ?? 0 }}, {{ $bookingTypeData['overnight'] ?? 0 }}, {{ $bookingTypeData[null] ?? 0 }}],
                                backgroundColor: [window.siteThemeColor(), '#f59e0b', '#e5e7eb'],
                                borderWidth: 0,
                                hoverOffset: 8,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 12, family: 'Inter' } }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255,255,255,0.95)',
                                    titleColor: '#111827',
                                    bodyColor: '#6b7280',
                                    borderColor: '#e5e7eb',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 10,
                                    boxPadding: 4,
                                    callbacks: {
                                        label: function(ctx) {
                                            let total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                            let pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                            return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                        }
                                    }
                                }
                            }
                        },
                        plugins: [{
                            id: 'centerText',
                            beforeDraw(chart) {
                                const { width, height, ctx } = chart;
                                ctx.save();
                                const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                ctx.font = '700 28px Inter, sans-serif';
                                ctx.fillStyle = '#111827';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(total, width / 2, height / 2 - 6);
                                ctx.font = '11px Inter, sans-serif';
                                ctx.fillStyle = '#9ca3af';
                                ctx.fillText('Total', width / 2, height / 2 + 18);
                                ctx.restore();
                            }
                        }]
                    })
                "></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 dark:text-white">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    Revenue (Last 6 Months)
                </h2>
                <span class="text-xs text-gray-500 font-medium dark:text-slate-400">₱ {{ number_format($revenueData->sum(), 2) }} total</span>
            </div>
            <div class="relative chart-container" style="max-height: 300px;">
                <canvas id="revenueChart" role="img" aria-label="Revenue over the last 6 months line chart" x-data x-init="
                    new Chart($el, {
                        type: 'line',
                        data: {
                            labels: [@foreach($revenueData as $month => $total)'{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}',@endforeach],
                            datasets: [{
                                label: 'Revenue',
                                data: [{{ $revenueData->values()->implode(',') }}],
                                borderColor: window.siteThemeColor(),
                                backgroundColor: function(ctx) {
                                    const tc = window.siteThemeColor();
                                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
                                    g.addColorStop(0, tc + '40');
                                    g.addColorStop(1, tc + '00');
                                    return g;
                                },
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: window.siteThemeColor(),
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHoverBorderWidth: 3,
                                borderWidth: 2.5,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(255,255,255,0.95)',
                                    titleColor: '#111827',
                                    bodyColor: '#6b7280',
                                    borderColor: '#e5e7eb',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 10,
                                    callbacks: {
                                        label: function(ctx) { return '₱' + ctx.parsed.y.toLocaleString(); }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                                    border: { display: false },
                                    ticks: {
                                        font: { size: 11, family: 'Inter' },
                                        color: '#9ca3af',
                                        callback: function(v) { return '₱' + v.toLocaleString(); }
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    border: { display: false },
                                    ticks: {
                                        font: { size: 11, family: 'Inter' },
                                        color: '#9ca3af',
                                        maxTicksLimit: 6
                                    }
                                }
                            }
                        }
                    })
                "></canvas>
            </div>
        </div>
    </div>

    {{-- Popular Cottages --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 dark:text-white">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Popular Cottages
            </h2>
            @if($popularCottages->isNotEmpty())
                <span class="text-xs text-gray-500 font-medium dark:text-slate-400">{{ $popularCottages->count() }} cottages</span>
            @endif
        </div>
        @if($popularCottages->isEmpty())
            @include('admin.components.empty-state', ['title' => 'No cottage data', 'message' => 'Book some cottages to see popularity data.'])
        @else
            @php $maxBookings = $popularCottages->max('inquiries_count'); @endphp

            {{-- Desktop table --}}
            <div class="desktop-table overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase tracking-wider dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Cottage</th>
                            <th class="text-center px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Bookings</th>
                            <th class="text-left px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Popularity</th>
                            <th class="text-center px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Max Pax</th>
                            <th class="text-right px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Day Tour</th>
                            <th class="text-right px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Overnight</th>
                            <th class="text-center px-5 py-3 font-medium bg-gray-50/50 dark:bg-slate-800/50">Available</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($popularCottages as $cottage)
                        @php
                            $pct = $maxBookings > 0 ? round(($cottage->inquiries_count / $maxBookings) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white">{{ $cottage->name }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-teal-50 text-teal-700 text-xs font-bold dark:bg-teal-900/30 dark:text-teal-300">{{ $cottage->inquiries_count }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="popularity-bar flex-1 max-w-[120px]">
                                        <div class="popularity-bar-fill" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 font-medium w-8 dark:text-slate-400">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center text-gray-600 dark:text-slate-300">{{ $cottage->capacity }}</td>
                            <td class="px-5 py-3.5 text-right text-gray-600 font-medium dark:text-slate-300">₱ {{ number_format($cottage->rate_daytour, 2) }}</td>
                            <td class="px-5 py-3.5 text-right text-gray-600 font-medium dark:text-slate-300">₱ {{ number_format($cottage->rate_overnight, 2) }}</td>
                            <td class="px-5 py-3.5 text-center">
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

            {{-- Mobile cards --}}
            <div class="mobile-table-cards p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($popularCottages as $cottage)
                    @php $pct = $maxBookings > 0 ? round(($cottage->inquiries_count / $maxBookings) * 100) : 0; @endphp
                    <div class="bg-gray-50/50 rounded-xl p-4 border border-gray-100 space-y-3 dark:bg-slate-800/50 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $cottage->name }}</span>
                            @if($cottage->is_available)
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Bookings</span>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-teal-50 text-teal-700 text-xs font-bold dark:bg-teal-900/30 dark:text-teal-300">{{ $cottage->inquiries_count }}</span>
                        </div>
                        <div class="popularity-bar">
                            <div class="popularity-bar-fill" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 dark:text-slate-400">Day Tour</span>
                                <p class="font-medium text-gray-700 dark:text-slate-200">₱ {{ number_format($cottage->rate_daytour, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-slate-400">Overnight</span>
                                <p class="font-medium text-gray-700 dark:text-slate-200">₱ {{ number_format($cottage->rate_overnight, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-slate-400">Max Pax</span>
                                <p class="font-medium text-gray-700 dark:text-slate-200">{{ $cottage->capacity }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-slate-400">Popularity</span>
                                <p class="font-medium text-gray-700 dark:text-slate-200">{{ $pct }}%</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush