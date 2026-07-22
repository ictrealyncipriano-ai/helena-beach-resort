<nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-teal-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-20">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach" class="h-8 w-auto rounded">
                <span class="font-semibold text-xl text-teal-700">Helena Beach</span>
            </a>

            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-sm font-medium text-gray-600 hover:text-teal-600 transition-colors">Home</a>
                <a href="{{ route('about') }}" class="text-sm font-medium text-gray-600 hover:text-teal-600 transition-colors">About</a>
                <a href="{{ route('cottages.index') }}" class="text-sm font-medium text-gray-600 hover:text-teal-600 transition-colors">Cottages</a>
                <a href="{{ route('gallery.index') }}" class="text-sm font-medium text-gray-600 hover:text-teal-600 transition-colors">Gallery</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-gray-600 hover:text-teal-600 transition-colors">Contact</a>
                <div class="flex items-center gap-3">
                    <a href="{{ App\Models\SiteSetting::getValue('facebook_url', '#') }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-teal-600 transition-colors" aria-label="Facebook">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-full hover:bg-teal-700 transition-colors">Book Now</a>
                </div>
            </div>

            <button type="button" id="mobile-menu-btn" class="md:hidden p-2 text-gray-600 hover:text-teal-600" aria-label="Toggle menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    <div id="mobile-menu" class="hidden md:hidden border-t border-teal-100 bg-white">
        <div class="px-4 py-4 space-y-3">
            <a href="{{ route('home') }}" class="block text-sm font-medium text-gray-600 hover:text-teal-600">Home</a>
            <a href="{{ route('about') }}" class="block text-sm font-medium text-gray-600 hover:text-teal-600">About</a>
            <a href="{{ route('cottages.index') }}" class="block text-sm font-medium text-gray-600 hover:text-teal-600">Cottages</a>
            <a href="{{ route('gallery.index') }}" class="block text-sm font-medium text-gray-600 hover:text-teal-600">Gallery</a>
            <a href="{{ route('contact') }}" class="block text-sm font-medium text-gray-600 hover:text-teal-600">Contact</a>
            <hr class="border-gray-100">
            <a href="{{ App\Models\SiteSetting::getValue('facebook_url', '#') }}" target="_blank" rel="noopener noreferrer" class="block text-sm font-medium text-gray-600 hover:text-teal-600">Facebook</a>
            <a href="{{ route('contact') }}" class="block text-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-full">Book Now</a>
        </div>
    </div>
</nav>

<script>
document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>
