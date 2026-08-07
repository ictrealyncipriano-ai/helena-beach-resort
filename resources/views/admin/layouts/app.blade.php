<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <script>
        // Apply light/dark before first paint to avoid a flash.
        (function () {
            var mode = localStorage.getItem('theme') || 'system';
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — Helena Beach Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Load webfonts asynchronously so they never block first paint.
         font-display: swap (already in the URL) shows fallback text until
         the fonts arrive. --}}
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript>
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" />
    </noscript>
    {{-- flatpickr CSS is bundled via Vite (see resources/js/admin.js). --}}
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-slate-900 dark:text-slate-100" x-data="adminLayout()" x-on:keydown.escape="sidebarOpen = false">
    <a href="#admin-main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:bg-teal-700 focus:text-white focus:rounded-lg focus:font-medium focus:text-sm">Skip to main content</a>

    <div class="flex h-screen overflow-hidden">
        @include('admin.layouts.partials.sidebar')

        <div class="flex flex-1 flex-col overflow-hidden">
            @include('admin.layouts.partials.topbar')

            <main id="admin-main-content" class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{-- Page Header --}}
                @hasSection('header')
                    <div class="mb-6 sm:mb-8">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">@yield('header')</h1>
                        @hasSection('description')
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">@yield('description')</p>
                        @endif
                    </div>
                @endif

                {{-- Breadcrumb --}}
                @hasSection('breadcrumb')
                    <div class="mb-4 text-sm text-gray-500 dark:text-slate-400">@yield('breadcrumb')</div>
                @endif

                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 flex items-center gap-2 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" role="status">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="flex-1">{{ session('success') }}</span>
                        <button type="button" @@click="show = false" class="text-emerald-500 hover:text-emerald-700">&times;</button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-center gap-2 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-300" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" role="alert">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span class="flex-1">{{ session('error') }}</span>
                        <button type="button" @@click="show = false" class="text-red-500 hover:text-red-700">&times;</button>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="border-t border-gray-100 bg-white px-6 py-3 text-xs text-gray-500 text-center dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                &copy; {{ date('Y') }} Helena Beach Resort. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        function adminLayout() {
            return {
                sidebarOpen: false,
                userMenuOpen: false,
            }
        }
    </script>
    @stack('scripts')
</body>
</html>