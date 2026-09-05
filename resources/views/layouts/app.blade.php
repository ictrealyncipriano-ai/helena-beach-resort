<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Helena Beach Resort — Experience paradise in Infanta, Quezon. Beachfront cottages, fresh seafood, and unforgettable memories.')">
    <link rel="canonical" href="@yield('canonical', \Illuminate\Support\Str::before(url()->current(), '?'))" />
    @hasSection('robots')
    <meta name="robots" content="@yield('robots')" />
    @endif

    <meta property="og:title" content="@yield('og_title', config('app.name'))" />
    <meta property="og:description" content="@yield('og_description', 'Experience paradise in Infanta, Quezon. Beachfront cottages, fresh seafood, and unforgettable memories.')" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:image" content="@yield('og_image', $site['og_image'])" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:image:alt" content="@yield('og_image_alt', config('app.name'))" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />
    <meta property="og:locale" content="en_PH" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@yield('og_title', config('app.name'))" />
    <meta name="twitter:description" content="@yield('og_description', 'Experience paradise in Infanta, Quezon.')" />
    <meta name="twitter:image" content="@yield('og_image', $site['og_image'])" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Load webfonts asynchronously so they never block first paint.
         font-display: swap (already in the URL) shows fallback text until
         the fonts arrive. --}}
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript>
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" />
    </noscript>
    <noscript>
        <style>.reveal { opacity: 1 !important; transform: none !important; }</style>
    </noscript>
    @php
        $ga4Id = $analytics['ga4_id'];
        $consentRequired = $analytics['consent_required'];
    @endphp
    @if($ga4Id)
    <script>
    // Google Analytics 4 with consent mode v2. The gtag.js script is NOT
    // fetched until the visitor has granted consent (or consent is disabled
    // in site settings); consent state is stored in the helena_consent cookie.
    (function () {
        window.helenaGa4Id = @json($ga4Id);
        window.helenaConsentRequired = {{ $consentRequired ? 'true' : 'false' }};

        window.loadHelenaGtm = function () {
            if (window.helenaGtmLoaded) return;
            var id = window.helenaGa4Id;
            if (!id) return;
            window.helenaGtmLoaded = true;

            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + id;
            s.onload = function () {
                window.gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'analytics_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted'
                });
                window.gtag('config', id, { 'send_page_view': true });
            };
            document.head.appendChild(s);
        };

        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };

        var match = document.cookie.match(/(?:^|; )helena_consent=([^;]*)/);
        var granted = !window.helenaConsentRequired ||
            (match && decodeURIComponent(match[1]) === 'granted');

        window.gtag('consent', 'default', {
            'ad_storage': granted ? 'granted' : 'denied',
            'analytics_storage': granted ? 'granted' : 'denied',
            'ad_user_data': granted ? 'granted' : 'denied',
            'ad_personalization': granted ? 'granted' : 'denied'
        });

        if (granted) {
            window.loadHelenaGtm();
        }
    })();
    </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @stack('head')
</head>
<body class="font-sans antialiased text-gray-800 bg-white dark:bg-slate-900 dark:text-slate-100" x-data="{ mobileMenu: false }" x-on:keydown.escape="mobileMenu = false">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:bg-teal-700 focus:text-white focus:rounded-lg focus:font-medium focus:text-sm">Skip to main content</a>

    <x-navbar />

    <main id="main-content">
        @yield('content')
    </main>

    <x-footer />

    <x-cookie-banner />

    {{-- Scroll reveal observer --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -60px 0px', threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });
    </script>

    @stack('scripts')
</body>
</html>
