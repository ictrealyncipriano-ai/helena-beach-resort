@props(['name' => 'search', 'placeholder' => 'Search...', 'value' => '', 'live' => false])

<div class="relative flex-1 min-w-[180px]">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
    <label class="sr-only" for="admin-search-{{ $name }}">{{ $placeholder }}</label>
    <input type="text" id="admin-search-{{ $name }}" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
        @if($live) x-model.debounce.300ms="search" @endif
        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
</div>
