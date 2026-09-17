@props(['solid' => false])
@php
    $routes = [
        'home' => 'Home',
        'about' => 'About',
        'cottages.index' => 'Cottages',
        'gallery.index' => 'Gallery',
        'services' => 'Services',
        'faq' => 'FAQ',
        'reviews' => 'Reviews',
        'news.index' => 'News',
        'contact' => 'Contact',
    ];
    $current = Route::currentRouteName();
    // Server-rendered solid state for light-top pages (no-JS fallback) and
    // pages that opt out of the transparent-over-hero treatment.
    $solidNav = (bool) $solid;
    $solidBg = 'bg-white shadow-sm border-b border-teal-100 dark:bg-slate-900 dark:border-slate-700';
@endphp

<header>
<nav aria-label="Primary" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 {{ $solidNav ? $solidBg : 'bg-transparent border-b border-transparent' }}"
     x-data="{ scrolled: false, solid: {{ $solidNav ? 'true' : 'false' }} }"
     x-init="scrolled = window.scrollY > 20"
     x-on:scroll.window="scrolled = window.scrollY > 20"
     :class="(scrolled || solid) ? '{{ $solidBg }}' : 'bg-transparent border-b border-transparent'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-20">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <img src="{{ $site['logo'] ?? asset('images/logo.jpg') }}" alt="{{ $site['name'] ?? config('app.name') }}" class="h-8 w-auto rounded transition-transform group-hover:scale-105">
                <span class="font-semibold text-xl transition-colors {{ $solidNav ? 'text-teal-700 dark:text-teal-300' : 'text-white' }}"
                      :class="(scrolled || solid) ? 'text-teal-700 dark:text-teal-300' : 'text-white'">{{ $site['name'] ?? config('app.name') }}</span>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden md:flex items-center gap-1">
                @foreach($routes as $route => $label)
                <a href="{{ route($route) }}"
                   class="px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                   @if($current === $route)
                   :class="(scrolled || solid) ? 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/40' : 'text-white bg-white/15'"
                   @else
                   :class="(scrolled || solid) ? 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' : 'text-white/90 hover:text-white hover:bg-white/10'"
                   @endif>
                    {{ $label }}
                </a>
                @endforeach
                <div class="flex items-center gap-3 ml-3 pl-3 border-l transition-colors {{ $solidNav ? 'border-gray-200 dark:border-slate-700' : 'border-white/20' }}"
                     :class="(scrolled || solid) ? 'border-gray-200 dark:border-slate-700' : 'border-white/20'">
                    <a href="{{ route('booking.portal.lookup') }}"
                       class="text-sm font-medium transition-colors"
                       @if($current === 'booking.portal.lookup' || str_starts_with((string) $current, 'booking.portal'))
                       :class="(scrolled || solid) ? 'text-teal-700 dark:text-teal-300' : 'text-white'"
                       @else
                       :class="(scrolled || solid) ? 'text-gray-500 hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-300' : 'text-white/80 hover:text-white'"
                       @endif>
                        My Booking
                    </a>
                    <x-theme-toggle />
                    @foreach($socials as $icon => $href)
                        @if($href)
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer"
                           class="transition-colors"
                           :class="(scrolled || solid) ? 'text-gray-500 hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-300' : 'text-white/80 hover:text-white'" aria-label="{{ ucfirst($icon) }}">
                            <x-icons name="{{ $icon }}" class="w-5 h-5" />
                        </a>
                        @endif
                    @endforeach
                    <a href="{{ route('book') }}"
                       class="inline-flex items-center px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-full hover:bg-teal-800 transition-all hover:shadow-lg hover:shadow-teal-600/20 active:scale-95">
                        Book Now
                    </a>
                </div>
            </div>

            {{-- Mobile Menu Button --}}
            <button type="button"
                    class="md:hidden min-w-[44px] min-h-[44px] p-3 flex items-center justify-center rounded-lg transition-colors {{ $solidNav ? 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' : 'text-white hover:bg-white/10' }}"
                    :class="(scrolled || solid) ? 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' : 'text-white hover:bg-white/10'"
                    aria-label="Toggle menu"
                    :aria-expanded="mobileMenu"
                    aria-controls="mobile-menu-drawer"
                    @click="mobileMenu = !mobileMenu">
                <template x-if="!mobileMenu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </template>
                <template x-if="mobileMenu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </template>
            </button>
        </div>
    </div>
</nav>

    {{-- Mobile Drawer (sibling of the blurred <nav>, not its child:
         backdrop-blur creates a containing block for fixed descendants,
         which used to clip this panel to the nav bar over hero sections) --}}
    <div x-show="mobileMenu"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="md:hidden fixed inset-0 z-40 bg-black/30"
         @click="mobileMenu = false">
    </div>
    <div x-show="mobileMenu"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         id="mobile-menu-drawer"
         role="dialog"
         aria-modal="true"
         aria-label="Menu"
         x-trap="mobileMenu"
         class="md:hidden fixed top-0 right-0 bottom-0 z-50 w-[min(20rem,85vw)] bg-white shadow-2xl dark:bg-slate-800 dark:border-l dark:border-slate-700">
        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-100 dark:border-slate-700">
            <span class="font-semibold text-teal-700 dark:text-teal-300">Menu</span>
            <div class="flex items-center gap-2">
                <x-theme-toggle class="[&_.theme-items]:w-36 [&_.theme-items]:-right-3" />
                <button type="button" class="min-w-[44px] min-h-[44px] p-3 flex items-center justify-center text-gray-500 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200" @click="mobileMenu = false" aria-label="Close menu" x-ref="closeMenu">
                    <x-icons name="x" class="w-5 h-5" />
                </button>
            </div>
        </div>
        <div class="px-4 py-4 space-y-1 overflow-y-auto max-h-[calc(100vh-4rem)]">
            @foreach($routes as $route => $label)
            <a href="{{ route($route) }}"
               class="flex items-center px-4 min-h-[44px] text-sm font-medium rounded-lg transition-colors
               {{ $current === $route ? 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/40' : 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' }}"
               @click="mobileMenu = false">
                {{ $label }}
            </a>
            @endforeach
            <hr class="my-3 border-gray-100 dark:border-slate-700">
            <a href="{{ route('booking.portal.lookup') }}"
               class="flex items-center px-4 min-h-[44px] text-sm font-medium rounded-lg transition-colors {{ str_starts_with($current, 'booking.portal') ? 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/40' : 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' }}"
               @click="mobileMenu = false">
                My Booking
            </a>
            <div class="flex items-center gap-2 px-4 py-3">
                @foreach($socials as $icon => $href)
                    @if($href)
                    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer"
                       class="min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-500 hover:text-teal-700 dark:text-slate-300 dark:hover:text-teal-300 transition-colors" aria-label="{{ ucfirst($icon) }}"
                       @click="mobileMenu = false">
                        <x-icons name="{{ $icon }}" class="w-5 h-5" />
                    </a>
                    @endif
                @endforeach
            </div>
            <div class="pt-3">
                <a href="{{ route('book') }}"
                   class="flex items-center justify-center text-center px-4 py-3 min-h-[44px] bg-teal-700 text-white text-sm font-medium rounded-full hover:bg-teal-800 transition-colors"
                   @click="mobileMenu = false">
                    Book Now
                </a>
            </div>
        </div>
    </div>
</header>
