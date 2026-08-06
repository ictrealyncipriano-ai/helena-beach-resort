@extends('layouts.app')

@section('title', 'Cottages')
@section('description', 'Browse our beachfront cottages at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<x-hero title="Our Cottages" subtitle="Choose the perfect cottage for your beach getaway." />

<section class="py-20 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($cottages->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-6 text-gray-400 dark:text-slate-500">
                <x-icons name="building" class="w-8 h-8" />
            </div>
            <h2 class="text-xl font-semibold text-gray-600 dark:text-slate-300">No cottages available at the moment</h2>
            <p class="text-gray-400 dark:text-slate-500 mt-2">Please check back soon or contact us for more information.</p>
        </div>
        @else
        <div x-data="cottageFilter()" class="space-y-8">
            {{-- Filter Bar --}}
            <div class="bg-gray-50 dark:bg-slate-800/50 rounded-2xl p-4 sm:p-6 border border-gray-100 dark:border-slate-700 reveal">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">
                            <x-icons name="users" class="w-3 h-3 inline -mt-0.5 mr-1 text-gray-400 dark:text-slate-500" />
                            Capacity
                        </label>
                        <select x-model="filters.capacity" class="w-full px-3 py-2 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none bg-white">
                            <option value="">Any</option>
                            <option value="2">2 guests</option>
                            <option value="4">4 guests</option>
                            <option value="6">6 guests</option>
                            <option value="8">8 guests</option>
                            <option value="12">12 guests</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">
                            <x-icons name="tag" class="w-3 h-3 inline -mt-0.5 mr-1 text-gray-400 dark:text-slate-500" />
                            Max Day Tour Rate
                        </label>
                        <input type="number" x-model="filters.maxPrice" placeholder="Any" class="w-full px-3 py-2 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">
                            <x-icons name="list" class="w-3 h-3 inline -mt-0.5 mr-1 text-gray-400 dark:text-slate-500" />
                            Sort by
                        </label>
                        <select x-model="filters.sort" class="w-full px-3 py-2 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none bg-white">
                            <option value="sort_order">Default</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name">Name</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg hover:border-teal-300 dark:hover:border-teal-400 transition-colors">
                            <input type="checkbox" x-model="filters.availableOnly" class="w-4 h-4 text-teal-600 border-gray-300 dark:border-slate-600 rounded focus:ring-teal-500">
                            <span class="text-sm text-gray-600 dark:text-slate-300">Available only</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Results Count --}}
            <div class="flex items-center justify-between reveal">
                <p class="text-sm text-gray-500 dark:text-slate-400" x-text="filteredCount() + ' cottage' + (filteredCount() !== 1 ? 's' : '') + ' found'"></p>
            </div>

            {{-- Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($cottages as $cottage)
                <a href="{{ route('cottages.show', $cottage) }}"
                   x-show="matches({{ $cottage->id }})"
                   x-transition
                   class="group bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 dark:border-slate-700 transition-all duration-300"
                   x-bind:data-capacity="{{ $cottage->capacity }}"
                   x-bind:data-price="{{ $cottage->rate_daytour ?? 0 }}"
                   x-bind:data-name="{{ json_encode($cottage->name) }}"
                   x-bind:data-available="{{ json_encode($cottage->is_available) }}"
                   x-bind:data-sort="{{ $cottage->sort_order }}">
                    <div class="aspect-[4/3] bg-teal-50 dark:bg-teal-900/30 overflow-hidden relative">
                        @if($cottage->primaryPhoto)
                        <img src="{{ Storage::url($cottage->primaryPhoto->photo_path) }}" alt="{{ $cottage->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-teal-300">
                            <x-icons name="building" class="w-16 h-16" />
                        </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-5">
                            <span class="text-white text-sm font-medium flex items-center gap-1">
                                <x-icons name="arrow-right" class="w-4 h-4" />
                                View Details
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-300 transition-colors">{{ $cottage->name }}</h3>
                            @if(!$cottage->is_available)
                            <span class="text-xs font-medium text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded-full">Unavailable</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-slate-400 line-clamp-2">{{ Str::limit(strip_tags($cottage->description), 120) }}</p>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                            <span class="text-sm text-gray-500 dark:text-slate-400 inline-flex items-center gap-1.5">
                                <x-icons name="users" class="w-4 h-4 text-gray-400 dark:text-slate-500" />
                                Up to {{ $cottage->capacity }} guests
                            </span>
                            <div class="text-right">
                                @if($cottage->rate_daytour)
                                <div class="text-xs text-gray-400 dark:text-slate-500">Day Tour</div>
                                <div class="text-sm font-semibold text-teal-600 dark:text-teal-300">₱{{ number_format($cottage->rate_daytour) }}</div>
                                @endif
                                @if($cottage->rate_overnight)
                                <div class="text-xs text-gray-400 dark:text-slate-500 mt-1">Overnight</div>
                                <div class="text-sm font-semibold text-teal-600 dark:text-teal-300">₱{{ number_format($cottage->rate_overnight) }}</div>
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
