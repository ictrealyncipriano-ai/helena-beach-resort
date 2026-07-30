@props(['name' => 'modal', 'size' => 'md', 'title' => ''])

@php
$sizes = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
];
@endphp

<div x-data="{
    isOpen: false,
    title: '{{ $title }}',
    data: {},
}"
     x-on:open-modal-{{ $name }}.window="isOpen = true; title = $event.detail?.title || '{{ $title }}'; data = $event.detail?.data || {}"
     x-on:close-modal-{{ $name }}.window="isOpen = false; data = {}"
     x-show="isOpen"
     x-trap.noscroll="isOpen"
     class="relative z-50"
     x-cloak>
    <div x-show="isOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4" class="relative bg-white rounded-xl shadow-2xl w-full {{ $sizes[$size] ?? 'max-w-lg' }} max-h-[90vh] overflow-y-auto" @@click.outside="isOpen = false">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="title"></h3>
                <button type="button" @@click="isOpen = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
