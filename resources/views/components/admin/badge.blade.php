@props(['type' => 'gray', 'size' => 'sm'])

@php
$colors = [
    'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
    'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
    'danger' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30',
    'info' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30',
    'gray' => 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30',
    'primary' => 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-400/30',
];
$sizes = [
    'sm' => 'px-1.5 py-0.5 text-xs',
    'md' => 'px-2 py-1 text-sm',
];
@endphp

<span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset {{ $colors[$type] ?? $colors['gray'] }} {{ $sizes[$size] ?? $sizes['sm'] }}">
    {{ $slot }}
</span>