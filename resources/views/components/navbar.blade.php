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
    $socialLinks = [
        'facebook' => 'facebook_url',
        'instagram' => 'instagram_url',
        'tiktok' => 'tiktok_url',
    ];
    $socialHrefs = [];
    foreach ($socialLinks as $icon => $settingKey) {
        $url = (string) App\Models\SiteSetting::getValue($settingKey, '');
        $socialHrefs[$icon] = \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : '';
    }
@endphp

<nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
     x-data="{ scrolled: false }"
     x-on:scroll.window="scrolled = window.scrollY > 20"
     :class="scrolled ? 'bg-white shadow-sm border-b border-teal-100 dark:bg-slate-900 dark:border-slate-700' : 'bg-white/80 backdrop-blur-md border-b border-transparent dark:bg-slate-900/80'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-20">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach" class="h-8 w-auto rounded transition-transform group-hover:scale-105">
                <span class="font-semibold text-xl text-teal-700 dark:text-teal-300">Helena Beach</span>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden md:flex items-center gap-1">
                @foreach($routes as $route => $label)
                <a href="{{ route($route) }}"
                   class="px-3 py-2 text-sm font-medium rounded-lg transition-colors
                   {{ $current === $route ? 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/40' : 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' }}">
                    {{ $label }}
                </a>
                @endforeach
                <div class="flex items-center gap-3 ml-3 pl-3 border-l border-gray-200 dark:border-slate-700">
                    <a href="{{ route('booking.portal.lookup') }}"
                       class="text-sm font-medium transition-colors {{ $current === 'booking.portal.lookup' || str_starts_with($current, 'booking.portal') ? 'text-teal-700 dark:text-teal-300' : 'text-gray-500 hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-300' }}">
                        My Booking
                    </a>
                    <x-theme-toggle />
                    @foreach($socialHrefs as $icon => $href)
                        @if($href)
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer"
                           class="text-gray-500 hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-300 transition-colors" aria-label="{{ ucfirst($icon) }}">
                            <x-icons name="{{ $icon }}" class="w-5 h-5" />
                        </a>
                        @endif
                    @endforeach
                    <a href="{{ route('book') }}"
                       class="inline-flex items-center px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-full hover:bg-teal-700 transition-all hover:shadow-lg hover:shadow-teal-600/20 active:scale-95">
                        Book Now
                    </a>
                </div>
            </div>

            {{-- Mobile Menu Button --}}
            <button type="button"
                    class="md:hidden p-2 text-gray-600 hover:text-teal-700 rounded-lg hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50 transition-colors"
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

    {{-- Mobile Drawer --}}
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
         class="md:hidden fixed top-0 right-0 bottom-0 z-50 w-72 bg-white shadow-2xl dark:bg-slate-800 dark:border-l dark:border-slate-700">
        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-100 dark:border-slate-700">
            <span class="font-semibold text-teal-700 dark:text-teal-300">Menu</span>
            <div class="flex items-center gap-2">
                <x-theme-toggle class="[&_.theme-items]:w-36 [&_.theme-items]:-right-3" />
                <button type="button" class="p-2 text-gray-500 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200" @click="mobileMenu = false" aria-label="Close menu">
                    <x-icons name="x" class="w-5 h-5" />
                </button>
            </div>
        </div>
        <div class="px-4 py-4 space-y-1 overflow-y-auto max-h-[calc(100vh-4rem)]">
            @foreach($routes as $route => $label)
            <a href="{{ route($route) }}"
               class="block px-4 py-2.5 text-sm font-medium rounded-lg transition-colors
               {{ $current === $route ? 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/40' : 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' }}"
               @click="mobileMenu = false">
                {{ $label }}
            </a>
            @endforeach
            <hr class="my-3 border-gray-100 dark:border-slate-700">
            <a href="{{ route('booking.portal.lookup') }}"
               class="block px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ str_starts_with($current, 'booking.portal') ? 'text-teal-700 bg-teal-50 dark:text-teal-300 dark:bg-teal-900/40' : 'text-gray-600 hover:text-teal-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-teal-300 dark:hover:bg-slate-700/50' }}"
               @click="mobileMenu = false">
                My Booking
            </a>
            <div class="flex items-center gap-5 px-4 py-3">
                @foreach($socialHrefs as $icon => $href)
                    @if($href)
                    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer"
                       class="text-gray-500 hover:text-teal-700 dark:text-slate-300 dark:hover:text-teal-300 transition-colors" aria-label="{{ ucfirst($icon) }}"
                       @click="mobileMenu = false">
                        <x-icons name="{{ $icon }}" class="w-5 h-5" />
                    </a>
                    @endif
                @endforeach
            </div>
            <div class="pt-3">
                <a href="{{ route('book') }}"
                   class="block text-center px-4 py-3 bg-teal-700 text-white text-sm font-medium rounded-full hover:bg-teal-700 transition-colors"
                   @click="mobileMenu = false">
                    Book Now
                </a>
            </div>
        </div>
    </div>
</nav>
