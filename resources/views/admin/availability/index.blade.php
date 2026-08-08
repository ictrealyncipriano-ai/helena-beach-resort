@extends('admin.layouts.app')
@section('title', 'Availability')
@section('header', 'Availability')
@section('description', 'Monthly calendar of blockages per cottage.')

@php
    $typeStyles = [
        'pending' => 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-200',
        'booked' => 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-200',
        'manual' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-700 dark:text-red-200',
    ];
@endphp

@section('content')
<div class="space-y-6">
    {{-- Month nav --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.availability', ['month' => $prev]) }}" class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors" aria-label="Previous month">&larr;</a>
            <span class="text-sm font-semibold text-gray-900 dark:text-white min-w-[10rem] text-center">{{ $monthLabel }}</span>
            <a href="{{ route('admin.availability', ['month' => $next]) }}" class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors" aria-label="Next month">&rarr;</a>
        </div>

        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full border bg-amber-50 border-amber-200 dark:bg-amber-900/30 dark:border-amber-700"></span> Pending</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full border bg-emerald-50 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-700"></span> Booked</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full border bg-red-50 border-red-200 dark:bg-red-900/30 dark:border-red-700"></span> Manual block</span>
        </div>
    </div>

    @foreach($calendar as $entry)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entry['cottage']->name }}</h2>
                <span class="text-xs text-gray-500 dark:text-slate-400">Max {{ $entry['cottage']->capacity }} pax</span>
            </div>
            <div class="p-4 overflow-x-auto">
                @if($entry['weeks'] === [])
                    <p class="text-sm text-gray-500 dark:text-slate-400">No data.</p>
                @else
                    <div class="grid grid-cols-7 gap-1.5 min-w-[640px]">
                        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                            <div class="text-center text-[11px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ $dayName }}</div>
                        @endforeach

                        @foreach($entry['weeks'] as $week)
                            @foreach($week as $day)
                                @if($day === null)
                                    <div></div>
                                @else
                                    @php
                                        $style = $day['type'] ? $typeStyles[$day['type']] : 'border-gray-100 text-gray-400 dark:border-slate-700 dark:text-slate-500';
                                        $highlight = $day['isToday'] ? ' ring-2 ring-teal-400' : '';
                                        $title = $day['reason'] ?: ($day['date'] . ' — available');
                                    @endphp
                                    <div title="{{ $title }}" class="rounded-lg border px-1 py-1.5 text-center text-[11px] leading-tight cursor-default{{ $highlight }} {{ $style }}">
                                        <span class="font-medium">{{ $day['dateLabel'] }}</span>
                                        @if($day['type'])
                                            <div class="text-[10px] opacity-80 truncate">{{ $day['inquiry']?->reference_code ?? 'Block' }}</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection