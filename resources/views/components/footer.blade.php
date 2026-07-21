<footer class="bg-teal-900 text-teal-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-2xl">🏖️</span>
                    <span class="font-semibold text-xl text-white">Helena Beach Resort</span>
                </div>
                <p class="text-teal-200 text-sm leading-relaxed">
                    Experience the perfect getaway at Helena Beach Resort. Nestled along the pristine shores of Infanta, Quezon, we offer a peaceful retreat surrounded by nature.
                </p>
                <div class="flex items-center gap-4 mt-4">
                    <a href="{{ App\Models\SiteSetting::getValue('facebook_url', '#') }}" target="_blank" rel="noopener noreferrer" class="text-teal-300 hover:text-white transition-colors" aria-label="Facebook">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="font-semibold text-white mb-4">Quick Links</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-teal-200 hover:text-white transition-colors">Home</a></li>
                    <li><a href="{{ route('about') }}" class="text-teal-200 hover:text-white transition-colors">About</a></li>
                    <li><a href="{{ route('cottages.index') }}" class="text-teal-200 hover:text-white transition-colors">Cottages</a></li>
                    <li><a href="{{ route('gallery.index') }}" class="text-teal-200 hover:text-white transition-colors">Gallery</a></li>
                    <li><a href="{{ route('contact') }}" class="text-teal-200 hover:text-white transition-colors">Contact Us</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-white mb-4">Contact Info</h3>
                <ul class="space-y-2 text-sm text-teal-200">
                    <li class="flex items-start gap-2">
                        <span>📍</span>
                        <span>Purok Buyan, Brgy. Dinahican, Infanta, Quezon</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span>📞</span>
                        <span>Contact number available upon request</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <span>📧</span>
                        <span>helenabeachresort@example.com</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-teal-800 mt-10 pt-6 text-center text-sm text-teal-300">
            &copy; {{ date('Y') }} Helena Beach Resort. All rights reserved.
        </div>
    </div>
</footer>
