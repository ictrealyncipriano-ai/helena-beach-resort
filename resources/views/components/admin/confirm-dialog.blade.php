@props([
    'name' => 'confirm',
    'title' => 'Are you sure?',
    'message' => 'This action cannot be undone.',
    'confirmText' => 'Confirm',
    'confirmClass' => 'bg-red-600 hover:bg-red-700 text-white',
    'cancelText' => 'Cancel',
])

<div x-data="{ open: false, actionUrl: '', actionMethod: 'POST', _previousFocus: null }"
     x-on:open-confirm-{{ $name }}.window="_previousFocus = document.activeElement; open = true; actionUrl = $event.detail.url; actionMethod = $event.detail.method || 'POST'"
     x-show="open"
     x-trap.noscroll="open"
     x-on:keydown.escape.window="if (open) { open = false; if (_previousFocus) { _previousFocus.focus(); _previousFocus = null; } }"
     class="relative z-50"
     x-cloak>
    <div x-show="open" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title-{{ $name }}" class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 dark:bg-slate-800 dark:border dark:border-slate-700">
            <div class="text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4 dark:bg-red-500/10">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </div>
                <h3 id="confirm-dialog-title-{{ $name }}" class="text-lg font-semibold text-gray-900 mb-2 dark:text-white">{{ $title }}</h3>
                <p class="text-sm text-gray-500 mb-6 dark:text-slate-400">{{ $message }}</p>
            </div>
            <div class="flex items-center justify-center gap-3">
                <button type="button" @@click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                    {{ $cancelText }}
                </button>
                <form :action="actionUrl" method="POST" class="inline" @@submit="$el.querySelector('button').disabled = true">
                    @csrf
                    <input type="hidden" name="_method" :value="actionMethod === 'GET' ? 'GET' : 'POST'">
                    <template x-if="actionMethod === 'DELETE'">
                        <input type="hidden" name="_method" value="DELETE">
                    </template>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors {{ $confirmClass }}">
                        {{ $confirmText }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>