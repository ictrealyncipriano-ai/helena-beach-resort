@props(['type' => 'gray', 'size' => 'sm'])

@php
$colors = [
    'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
    'info' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'gray' => 'bg-gray-50 text-gray-600 ring-gray-500/20',
    'primary' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
];
$sizes = [
    'sm' => 'px-1.5 py-0.5 text-xs',
    'md' => 'px-2 py-1 text-sm',
];
@endphp

<span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset {{ $colors[$type] ?? $colors['gray'] }} {{ $sizes[$size] ?? $sizes['sm'] }}">
    {{ $slot }}
</span>