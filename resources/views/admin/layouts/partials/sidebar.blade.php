@php
    $currentRoute = Route::currentRouteName();
    $navGroups = [
        'Content' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
            ['label' => 'Cottages', 'route' => 'admin.cottages.index', 'icon' => 'home-modern'],
            ['label' => 'News & Posts', 'route' => 'admin.posts.index', 'icon' => 'document-text'],
            ['label' => 'Testimonials', 'route' => 'admin.testimonials.index', 'icon' => 'star'],
            ['label' => 'Services', 'route' => 'admin.services.index', 'icon' => 'sparkles'],
            ['label' => 'FAQs', 'route' => 'admin.faqs.index', 'icon' => 'question-mark-circle'],
            ['label' => 'Gallery', 'route' => 'admin.gallery.index', 'icon' => 'photo'],
        ],
        'Bookings' => [
            ['label' => 'Inquiries', 'route' => 'admin.inquiries.index', 'icon' => 'chat-bubble-left'],
            ['label' => 'Guests', 'route' => 'admin.guests.index', 'icon' => 'user'],
            ['label' => 'Availability', 'route' => 'admin.availability', 'icon' => 'calendar'],
            ['label' => 'Reports', 'route' => 'admin.exports.index', 'icon' => 'document'],
        ],
        'Offers' => [
            ['label' => 'Promo Codes', 'route' => 'admin.promo-codes.index', 'icon' => 'tag'],
        ],
        'Settings' => [
            ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
            ['label' => 'Activity Logs', 'route' => 'admin.activity-logs.index', 'icon' => 'shield-check'],
            ['label' => 'Site Settings', 'route' => 'admin.site-settings.index', 'icon' => 'cog'],
        ],
    ];
@endphp

{{-- Mobile overlay --}}
<div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @@click="sidebarOpen = false"></div>

{{-- Sidebar --}}
<aside id="admin-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-60 bg-teal-800 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-auto">
    {{-- Brand --}}
    <div class="flex h-16 items-center gap-2.5 px-5 border-b border-teal-700/50 shrink-0">
        <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach" class="h-8 w-8 rounded-lg shadow-lg shrink-0">
        <span class="font-heading text-lg font-bold text-white leading-tight">Helena Beach</span>
    </div>

    {{-- Nav items --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        @foreach($navGroups as $group => $items)
            <div class="mb-1 px-3 pt-3 pb-1">
                <span class="text-xs font-semibold tracking-wider text-teal-300/60 uppercase">{{ $group }}</span>
            </div>
            @foreach($items as $item)
                @php $active = str_starts_with($currentRoute, $item['route']) || $currentRoute === $item['route']; @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 group"
                   :class="'{{ $active }}' ? 'bg-white text-teal-800 shadow-sm' : 'text-teal-100 hover:bg-teal-700/50 hover:text-white'">
                    <span class="shrink-0">@include('admin.layouts.partials.icon', ['name' => $item['icon'], 'active' => $active])</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- User footer --}}
    <div class="border-t border-teal-700/50 px-4 py-3">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 w-full px-3 py-2 text-sm font-medium text-teal-200 hover:text-white hover:bg-teal-700/50 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                <span>Sign out</span>
            </button>
        </form>
    </div>
</aside>