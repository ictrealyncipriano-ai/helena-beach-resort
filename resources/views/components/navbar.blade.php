@php
    $routes = [
        'home' => 'Home',
        'about' => 'About',
        'cottages.index' => 'Cottages',
        'gallery.index' => 'Gallery',
        'services' => 'Services',
        'faq' => 'FAQ',
        'reviews' => 'Reviews',
        'contact' => 'Contact',
    ];
    $current = Route::currentRouteName();
@endphp

<nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
     x-data="{ scrolled: false }"
     x-on:scroll.window="scrolled = window.scrollY > 20"
     :class="scrolled ? 'bg-white shadow-sm border-b border-teal-100' : 'bg-white/80 backdrop-blur-md border-b border-transparent'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-20">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach" class="h-8 w-auto rounded transition-transform group-hover:scale-105">
                <span class="font-semibold text-xl text-teal-700">Helena Beach</span>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden md:flex items-center gap-1">
                @foreach($routes as $route => $label)
                <a href="{{ route($route) }}"
                   class="px-3 py-2 text-sm font-medium rounded-lg transition-colors
                   {{ $current === $route ? 'text-teal-700 bg-teal-50' : 'text-gray-600 hover:text-teal-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
                @endforeach
                <div class="flex items-center gap-3 ml-3 pl-3 border-l border-gray-200">
                    <a href="{{ route('booking.portal.lookup') }}"
                       class="text-sm font-medium transition-colors {{ $current === 'booking.portal.lookup' || str_starts_with($current, 'booking.portal') ? 'text-teal-700' : 'text-gray-500 hover:text-teal-600' }}">
                        My Booking
                    </a>
                    <a href="{{ App\Models\SiteSetting::getValue('facebook_url', '#') }}" target="_blank" rel="noopener noreferrer"
                       class="text-gray-400 hover:text-teal-600 transition-colors" aria-label="Facebook">
                        <x-icons name="facebook" class="w-5 h-5" />
                    </a>
                    <a href="{{ route('book') }}"
                       class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-full hover:bg-teal-700 transition-all hover:shadow-lg hover:shadow-teal-600/20 active:scale-95">
                        Book Now
                    </a>
                </div>
            </div>

            {{-- Mobile Menu Button --}}
            <button type="button"
                    class="md:hidden p-2 text-gray-600 hover:text-teal-600 rounded-lg hover:bg-gray-50 transition-colors"
                    aria-label="Toggle menu"
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
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="md:hidden fixed top-0 right-0 bottom-0 z-50 w-72 bg-white shadow-2xl">
        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-100">
            <span class="font-semibold text-teal-700">Menu</span>
            <button type="button" class="p-2 text-gray-400 hover:text-gray-600" @click="mobileMenu = false">
                <x-icons name="x" class="w-5 h-5" />
            </button>
        </div>
        <div class="px-4 py-4 space-y-1 overflow-y-auto max-h-[calc(100vh-4rem)]">
            @foreach($routes as $route => $label)
            <a href="{{ route($route) }}"
               class="block px-4 py-2.5 text-sm font-medium rounded-lg transition-colors
               {{ $current === $route ? 'text-teal-700 bg-teal-50' : 'text-gray-600 hover:text-teal-600 hover:bg-gray-50' }}"
               @click="mobileMenu = false">
                {{ $label }}
            </a>
            @endforeach
            <hr class="my-3 border-gray-100">
            <a href="{{ route('booking.portal.lookup') }}"
               class="block px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ str_starts_with($current, 'booking.portal') ? 'text-teal-700 bg-teal-50' : 'text-gray-600 hover:text-teal-600 hover:bg-gray-50' }}"
               @click="mobileMenu = false">
                My Booking
            </a>
            <a href="{{ App\Models\SiteSetting::getValue('facebook_url', '#') }}" target="_blank" rel="noopener noreferrer"
               class="block px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-teal-600 rounded-lg hover:bg-gray-50"
               @click="mobileMenu = false">
                Facebook
            </a>
            <div class="pt-3">
                <a href="{{ route('book') }}"
                   class="block text-center px-4 py-3 bg-teal-600 text-white text-sm font-medium rounded-full hover:bg-teal-700 transition-colors"
                   @click="mobileMenu = false">
                    Book Now
                </a>
            </div>
        </div>
    </div>
</nav>
