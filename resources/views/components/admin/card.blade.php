@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700' . ($padding ? ' p-5 sm:p-6' : '')]) }}>
    {{ $slot }}
</div>
