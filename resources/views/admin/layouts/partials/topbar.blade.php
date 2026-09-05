<header class="flex h-16 shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-slate-700 dark:bg-slate-900">
    {{-- Mobile hamburger --}}
    <button type="button" class="lg:hidden -ml-1 p-2 text-gray-500 hover:text-teal-700 hover:bg-gray-100 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-slate-800" @@click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar" :aria-expanded="sidebarOpen" aria-controls="admin-sidebar">
        <x-icons name="menu" class="w-5 h-5" />
    </button>

    {{-- Breadcrumb --}}
    <div class="flex-1 min-w-0 hidden sm:block">
        @yield('breadcrumb')
    </div>

    {{-- Right side --}}
    <div class="flex items-center gap-3">
        <x-theme-toggle />
        {{-- User dropdown --}}
        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/50">
                <div class="w-7 h-7 rounded-full bg-teal-700 flex items-center justify-center text-white text-xs font-bold shrink-0">
                    {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                </div>
                <span class="text-sm font-medium text-gray-700 hidden md:block dark:text-slate-200">{{ Auth::user()->name ?? 'Admin' }}</span>
                <x-icons name="chevron-down" class="w-4 h-4 text-gray-500 dark:text-slate-400" />
            </button>

            {{-- Dropdown --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-4 top-14 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50 dark:bg-slate-800 dark:border-slate-700">
                <div class="px-4 py-2 border-b border-gray-50 dark:border-slate-700">
                    <p class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate dark:text-slate-400">{{ Auth::user()->email }}</p>
                </div>
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors dark:text-slate-200 dark:hover:bg-slate-700/50">
                    <x-icons name="external" class="w-4 h-4" />
                    Visit Website
                </a>
                <div class="border-t border-gray-50 mt-1 pt-1 dark:border-slate-700">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors dark:text-red-400 dark:hover:bg-red-500/10">
                            <x-icons name="logout" class="w-4 h-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>