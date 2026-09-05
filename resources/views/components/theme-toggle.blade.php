<div class="relative" x-data="themeToggle()" @click.outside="open = false" @keydown.escape.window="open = false" x-cloak>
    <button type="button"
            @click="open = !open"
            :aria-expanded="open"
            aria-haspopup="menu"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-teal-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:text-teal-400 dark:hover:bg-slate-700/50 transition-colors"
            aria-label="Toggle theme">
        <span x-show="dark" x-cloak style="display:none" class="inline-flex"><x-icons name="sun" class="w-5 h-5" /></span>
        <span x-show="!dark" x-cloak style="display:none" class="inline-flex"><x-icons name="moon" class="w-5 h-5" /></span>
    </button>

    <div x-show="open" style="display:none"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         x-cloak
         role="menu"
         class="absolute right-0 top-12 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 py-1 z-50">
        <p class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Theme</p>
        <button type="button" @click="set('light'); open = false" role="menuitemradio" :aria-checked="mode === 'light'" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors" :class="mode === 'light' ? 'font-semibold text-teal-700 dark:text-teal-400' : ''">
            <x-icons name="sun" class="w-4 h-4" />
            Light
        </button>
        <button type="button" @click="set('dark'); open = false" role="menuitemradio" :aria-checked="mode === 'dark'" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors" :class="mode === 'dark' ? 'font-semibold text-teal-700 dark:text-teal-400' : ''">
            <x-icons name="moon" class="w-4 h-4" />
            Dark
        </button>
        <button type="button" @click="set('system'); open = false" role="menuitemradio" :aria-checked="mode === 'system'" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors" :class="mode === 'system' ? 'font-semibold text-teal-700 dark:text-teal-400' : ''">
            <x-icons name="monitor" class="w-4 h-4" />
            System
        </button>
    </div>
</div>
