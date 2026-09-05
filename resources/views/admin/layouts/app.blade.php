<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
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
                    <x-admin.alert type="success">{{ session('success') }}</x-admin.alert>
                @endif
                @if (session('error'))
                    <x-admin.alert type="error">{{ session('error') }}</x-admin.alert>
                @endif
                @if (session('warning'))
                    <x-admin.alert type="warning">{{ session('warning') }}</x-admin.alert>
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