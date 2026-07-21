@extends('layouts.app')

@section('title', 'Cottages')
@section('description', 'Browse our beachfront cottages at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Our Cottages</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Choose the perfect cottage for your beach getaway.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($cottages->isEmpty())
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🏠</div>
            <h2 class="text-xl font-semibold text-gray-600">No cottages available at the moment</h2>
            <p class="text-gray-400 mt-2">Please check back soon or contact us for more information.</p>
        </div>
        @else
        <div x-data="cottageFilter()" class="space-y-8">
            {{-- Filter Bar --}}
            <div class="bg-gray-50 rounded-2xl p-4 sm:p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Capacity</label>
                        <select x-model="filters.capacity" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none bg-white">
                            <option value="">Any</option>
                            <option value="2">2 guests</option>
                            <option value="4">4 guests</option>
                            <option value="6">6 guests</option>
                            <option value="8">8 guests</option>
                            <option value="12">12 guests</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Max Day Tour Rate</label>
                        <input type="number" x-model="filters.maxPrice" placeholder="Any" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sort by</label>
                        <select x-model="filters.sort" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none bg-white">
                            <option value="sort_order">Default</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name">Name</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="filters.availableOnly" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <span class="text-sm text-gray-600">Available only</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Results Count --}}
            <p class="text-sm text-gray-500" x-text="filteredCount() + ' cottage' + (filteredCount() !== 1 ? 's' : '') + ' found'"></p>

            {{-- Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($cottages as $cottage)
                <a href="{{ route('cottages.show', $cottage) }}"
                   x-show="matches({{ $cottage->id }})"
                   x-transition
                   class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300"
                   x-bind:data-capacity="{{ $cottage->capacity }}"
                   x-bind:data-price="{{ $cottage->rate_daytour ?? 0 }}"
                   x-bind:data-name="{{ json_encode($cottage->name) }}"
                   x-bind:data-available="{{ json_encode($cottage->is_available) }}"
                   x-bind:data-sort="{{ $cottage->sort_order }}">
                    <div class="aspect-[4/3] bg-teal-50 overflow-hidden">
                        @if($cottage->primaryPhoto)
                        <img src="{{ Storage::url($cottage->primaryPhoto->photo_path) }}" alt="{{ $cottage->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-teal-300 text-6xl">🏠</div>
                        @endif
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors">{{ $cottage->name }}</h3>
                            @if(!$cottage->is_available)
                            <span class="text-xs font-medium text-red-500 bg-red-50 px-2 py-0.5 rounded-full">Unavailable</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 line-clamp-2">{{ Str::limit(strip_tags($cottage->description), 120) }}</p>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                            <span class="text-sm text-gray-500">👥 Up to {{ $cottage->capacity }} guests</span>
                            <div class="text-right">
                                @if($cottage->rate_daytour)
                                <div class="text-xs text-gray-400">Day Tour</div>
                                <div class="text-sm font-semibold text-teal-600">₱{{ number_format($cottage->rate_daytour) }}</div>
                                @endif
                                @if($cottage->rate_overnight)
                                <div class="text-xs text-gray-400 mt-1">Overnight</div>
                                <div class="text-sm font-semibold text-teal-600">₱{{ number_format($cottage->rate_overnight) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
function cottageFilter() {
    return {
        filters: {
            capacity: '',
            maxPrice: '',
            sort: 'sort_order',
            availableOnly: false,
        },
        matches(id) {
            const el = document.querySelector(`a[href*="/cottages/${id}"]`);
            if (!el) return true;
            const capacity = parseInt(el.dataset.capacity);
            const price = parseInt(el.dataset.price);
            const available = el.dataset.available === 'true';

            if (this.filters.availableOnly && !available) return false;
            if (this.filters.capacity && capacity < parseInt(this.filters.capacity)) return false;
            if (this.filters.maxPrice && price > parseInt(this.filters.maxPrice)) return false;

            return true;
        },
        filteredCount() {
            const links = document.querySelectorAll('a[data-capacity]');
            return Array.from(links).filter(el => {
                const id = el.href.split('/').pop();
                return this.matches(id);
            }).length;
        },
        init() {
            this.$watch('filters.sort', (val) => this.sortCards(val));
            this.$watch('filters', () => {
                this.$nextTick(() => this.sortCards(this.filters.sort));
            }, { deep: true });
        },
        sortCards(sort) {
            const container = this.$el.querySelector('.grid');
            const items = Array.from(container.querySelectorAll('a[data-capacity]'));
            items.sort((a, b) => {
                switch (sort) {
                    case 'price_low': return parseInt(a.dataset.price) - parseInt(b.dataset.price);
                    case 'price_high': return parseInt(b.dataset.price) - parseInt(a.dataset.price);
                    case 'name': return a.dataset.name.localeCompare(b.dataset.name);
                    default: return parseInt(a.dataset.sort) - parseInt(b.dataset.sort);
                }
            });
            items.forEach(item => container.appendChild(item));
        }
    };
}
</script>
@endpush
@endsection
