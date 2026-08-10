<footer class="bg-teal-900 text-teal-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-12">
            {{-- Brand --}}
            <div class="reveal">
                <div class="flex items-center gap-2 mb-4">
                    <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach Resort" class="h-8 w-auto rounded">
                    <span class="font-semibold text-xl text-white">Helena Beach Resort</span>
                </div>
                <p class="text-teal-200/80 text-sm leading-relaxed max-w-xs">
                    Experience the perfect getaway at Helena Beach Resort. Nestled along the pristine shores of Infanta, Quezon, we offer a peaceful retreat surrounded by nature.
                </p>
                <div class="flex items-center gap-3 mt-5">
                    @php
                        $socialLinks = [
                            'facebook' => 'facebook_url',
                            'instagram' => 'instagram_url',
                            'tiktok' => 'tiktok_url',
                        ];
                    @endphp
                    @foreach($socialLinks as $icon => $settingKey)
                        @php
                            $url = (string) App\Models\SiteSetting::getValue($settingKey, '');
                            $href = \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : '';
                        @endphp
                        @if($href)
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($icon) }}"
                           class="w-9 h-9 rounded-full bg-teal-800/50 flex items-center justify-center text-teal-300 hover:bg-teal-700 hover:text-white transition-all">
                            <x-icons name="{{ $icon }}" class="w-4 h-4" />
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="reveal reveal-delay-1">
                <h3 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Quick Links</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('home') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />Home</a></li>
                    <li><a href="{{ route('about') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />About</a></li>
                    <li><a href="{{ route('cottages.index') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />Cottages</a></li>
                    <li><a href="{{ route('gallery.index') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />Gallery</a></li>
                    <li><a href="{{ route('services') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />Services</a></li>
                    <li><a href="{{ route('news.index') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />News</a></li>
                    <li><a href="{{ route('faq') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />FAQ</a></li>
                    <li><a href="{{ route('reviews') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />Reviews</a></li>
                    <li><a href="{{ route('contact') }}" class="text-teal-200/80 hover:text-white transition-colors inline-flex items-center gap-1.5 group"><x-icons name="chevron-right" class="w-3 h-3 text-teal-400 group-hover:translate-x-0.5 transition-transform" />Contact Us</a></li>
                </ul>
            </div>

            {{-- Contact Info --}}
            <div class="reveal reveal-delay-2">
                <h3 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Contact Info</h3>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <span class="shrink-0 w-9 h-9 rounded-full bg-teal-800/50 flex items-center justify-center text-teal-300">
                            <x-icons name="location" class="w-4 h-4" />
                        </span>
                        <span class="text-teal-200/80 pt-1.5">Purok Buyan, Brgy. Dinahican, Infanta, Quezon</span>
                    </li>
                    @php
                        $contactPhone = trim((string) App\Models\SiteSetting::getValue('contact_phone', ''));
                    @endphp
                    @if($contactPhone && $contactPhone !== 'N/A')
                    <li class="flex items-start gap-3">
                        <span class="shrink-0 w-9 h-9 rounded-full bg-teal-800/50 flex items-center justify-center text-teal-300">
                            <x-icons name="phone" class="w-4 h-4" />
                        </span>
                        <a href="tel:{{ $contactPhone }}" class="text-teal-200/80 pt-1.5 hover:text-white transition-colors">{{ $contactPhone }}</a>
                    </li>
                    @endif
                    <li class="flex items-start gap-3">
                        <span class="shrink-0 w-9 h-9 rounded-full bg-teal-800/50 flex items-center justify-center text-teal-300">
                            <x-icons name="email" class="w-4 h-4" />
                        </span>
                        <span class="text-teal-200/80 pt-1.5 break-all">{{ App\Models\SiteSetting::getValue('contact_email', 'ict.realyncipriano@gmail.com') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-teal-800/60 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-teal-300/60">
            <p>&copy; {{ date('Y') }} Helena Beach Resort. All rights reserved.</p>
            <ul class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-white transition-colors">Terms &amp; Conditions</a></li>
                <li><a href="{{ route('booking-policy') }}" class="hover:text-white transition-colors">Booking Policy</a></li>
            </ul>
            <p class="text-teal-300/40 text-xs">Made with <x-icons name="heart" class="w-3 h-3 inline text-teal-400" /> in Infanta, Quezon</p>
        </div>
    </div>
</footer>
