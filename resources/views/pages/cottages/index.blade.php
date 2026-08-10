@extends('layouts.app')

@section('title', 'Cottages')
@section('description', 'Browse our beachfront cottages at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<x-hero title="Our Cottages" subtitle="Choose the perfect cottage for your beach getaway." />

<section class="py-20 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($cottages->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-6 text-gray-500 dark:text-slate-400">
                <x-icons name="building" class="w-8 h-8" />
            </div>
            <h2 class="text-xl font-semibold text-gray-600 dark:text-slate-300">No cottages available at the moment</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-2">Please check back soon or contact us for more information.</p>
        </div>
        @else
        <div class="mb-12 max-w-3xl mx-auto" x-data="availabilityWidget()" x-init="initWidget()">
            <div class="bg-teal-50 dark:bg-teal-900/40 rounded-2xl ring-1 ring-teal-100 dark:ring-teal-800 p-6 sm:p-8 text-left shadow-lg shadow-teal-900/5">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white font-heading">Check Availability</h2>
                    <x-icons name="calendar" class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="widget-cottage" class="block text-xs font-semibold tracking-wide text-gray-500 dark:text-slate-400 uppercase mb-1.5">Cottage</label>
                        <select id="widget-cottage" x-model="cottageId" @change="onCottageChange()"
                            class="w-full px-3 py-2.5 text-sm bg-white border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg focus:ring-2 focus:ring-teal-600 focus:border-teal-500 outline-none transition-colors">
                            <option value="">Choose a cottage</option>
                            @foreach($cottages as $cottage)
                            <option value="{{ $cottage->id }}" data-night="₱{{ number_format($cottage->rate_overnight) }}">{{ $cottage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="widget-check-in" class="block text-xs font-semibold tracking-wide text-gray-500 dark:text-slate-400 uppercase mb-1.5">Check-in</label>
                        <input type="text" id="widget-check-in" readonly x-ref="checkIn" x-model="checkIn"
                            @click.prevent="openCheckIn()" placeholder="Select date"
                            class="w-full px-3 py-2.5 text-sm bg-white border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg focus:ring-2 focus:ring-teal-600 focus:border-teal-500 outline-none transition-colors cursor-pointer">
                    </div>
                    <div x-show="bookingType === 'overnight'">
                        <label for="widget-check-out" class="block text-xs font-semibold tracking-wide text-gray-500 dark:text-slate-400 uppercase mb-1.5">Check-out</label>
                        <input type="text" id="widget-check-out" readonly x-ref="checkOut" x-model="checkOut"
                            @click.prevent="openCheckOut()" placeholder="Select date"
                            class="w-full px-3 py-2.5 text-sm bg-white border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg focus:ring-2 focus:ring-teal-600 focus:border-teal-500 outline-none transition-colors cursor-pointer">
                    </div>
                    <div class="sm:col-span-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3 rounded-xl bg-white dark:bg-slate-800">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-sm">Type:</span>
                                <select x-model="bookingType" @change="onTypeChange()"
                                    class="text-sm font-medium bg-white dark:bg-slate-800 border border-teal-200 dark:border-slate-600 text-gray-800 dark:text-slate-100 rounded-md px-2 py-1 focus:ring-2 focus:ring-teal-600 outline-none">
                                    <option value="day_tour">Day Tour</option>
                                    <option value="overnight">Overnight</option>
                                </select>
                                <template x-if="result">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-1 rounded-full"
                                        :class="result.available ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'">
                                        <x-icons name="check" class="w-3.5 h-3.5" x-show="result.available" />
                                        <x-icons name="x" class="w-3.5 h-3.5" x-show="!result.available" />
                                        <span x-text="result.available ? 'Available' : 'Not available'"></span>
                                    </span>
                                </template>
                            </div>
                            <template x-if="result && result.available">
                                <span class="text-sm font-bold text-teal-800 dark:text-teal-300" x-text="result.rate.label"></span>
                            </template>
                            <template x-if="busy">
                                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-slate-400">
                                    <x-icons name="spinner" class="w-4 h-4 animate-spin" />
                                    Checking availability…
                                </span>
                            </template>
                        </div>
                        <template x-if="result && !result.available">
                            <div class="mt-3 px-4 py-2.5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300">
                                <p class="font-medium mb-0.5">Blocked on: <span x-text="result.blocked_dates.join(', ')"></span></p>
                                <p class="text-xs opacity-90">Try different dates or browse <a href="{{ route('cottages.index') }}" class="underline">other cottages</a>.</p>
                            </div>
                        </template>
                    </div>
                </div>
                <a :href="bookUrl" :class="bookUrl ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-40'"
                    class="mt-5 w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-teal-700 text-white font-semibold rounded-xl hover:bg-teal-700 transition-colors active:scale-95">
                    Book This Cottage
                    <x-icons name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        </div>

        <div x-data="cottageFilter()" class="space-y-8">
            {{-- Filter Bar --}}
            <div class="bg-gray-50 dark:bg-slate-800/50 rounded-2xl p-4 sm:p-6 border border-gray-100 dark:border-slate-700 reveal">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">
                            <x-icons name="users" class="w-3 h-3 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                            Capacity
                        </label>
                        <select x-model="filters.capacity" class="w-full px-3 py-2 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-teal-700 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none bg-white">
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
                            <x-icons name="tag" class="w-3 h-3 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                            Max Day Tour Rate
                        </label>
                        <input type="number" x-model="filters.maxPrice" placeholder="Any" class="w-full px-3 py-2 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg text-sm focus:ring-2 focus:ring-teal-700 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">
                            <x-icons name="list" class="w-3 h-3 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                            Sort by
                        </label>
                        <select x-model="filters.sort" class="w-full px-3 py-2 border border-gray-200 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-teal-700 focus:border-teal-500 dark:focus:border-teal-500 dark:ring-teal-500/20 outline-none bg-white">
                            <option value="sort_order">Default</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name">Name</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-2 border border-gray-200 dark:border-slate-600 rounded-lg hover:border-teal-300 dark:hover:border-teal-400 transition-colors">
                            <input type="checkbox" x-model="filters.availableOnly" class="w-4 h-4 text-teal-700 border-gray-300 dark:border-slate-600 rounded focus:ring-teal-700">
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
                   x-show="matches($el)"
                   x-transition
                   class="group bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 dark:border-slate-700 transition-all duration-300"
                   x-bind:data-capacity="{{ $cottage->capacity }}"
                   x-bind:data-price="{{ $cottage->rate_daytour ?? 0 }}"
                   x-bind:data-name="{{ json_encode($cottage->name) }}"
                   x-bind:data-available="{{ json_encode($cottage->is_available) }}"
                   x-bind:data-sort="{{ $cottage->sort_order }}">
                    <div class="aspect-[4/3] bg-teal-50 dark:bg-teal-900/30 overflow-hidden relative">
                        @if($cottage->primaryPhoto)
                        <img src="{{ Storage::url($cottage->primaryPhoto->photo_path) }}" alt="{{ $cottage->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" decoding="async">
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
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-teal-700 dark:group-hover:text-teal-300 transition-colors">{{ $cottage->name }}</h3>
                            @if(!$cottage->is_available)
                            <span class="text-xs font-medium text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded-full">Unavailable</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-slate-400 line-clamp-2">{{ Str::limit(strip_tags($cottage->description), 120) }}</p>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-slate-700">
                            <span class="text-sm text-gray-500 dark:text-slate-400 inline-flex items-center gap-1.5">
                                <x-icons name="users" class="w-4 h-4 text-gray-500 dark:text-slate-400" />
                                Up to {{ $cottage->capacity }} guests
                            </span>
                            <div class="text-right">
                                @if($cottage->rate_daytour)
                                <div class="text-xs text-gray-500 dark:text-slate-400">Day Tour</div>
                                <div class="text-sm font-semibold text-teal-700 dark:text-teal-300">₱{{ number_format($cottage->rate_daytour) }}</div>
                                @endif
                                @if($cottage->rate_overnight)
                                <div class="text-xs text-gray-500 dark:text-slate-400 mt-1">Overnight</div>
                                <div class="text-sm font-semibold text-teal-700 dark:text-teal-300">₱{{ number_format($cottage->rate_overnight) }}</div>
                                @endif
                            </div>
                        </div>
                        <span class="mt-4 inline-flex items-center justify-center gap-1.5 w-full px-4 py-2.5 bg-teal-700 text-white text-sm font-medium rounded-xl group-hover:bg-teal-700 transition-colors">
                            <x-icons name="arrow-right" class="w-4 h-4" />
                            View Details
                        </span>
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
        matches(el) {
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
            const cards = document.querySelectorAll('a[data-capacity]');
            return Array.from(cards).filter(el => this.matches(el)).length;
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

        @push('scripts')
        @vite('resources/js/flatpickr.js')
        <script>
        function availabilityWidget() {
            const endpoint = @json(route('availability.check'));
            let fpIn = null;
            let fpOut = null;
            let initialised = false;

            return {
                cottageId: '',
                bookingType: 'overnight',
                checkIn: '',
                checkOut: '',
                result: null,
                busy: false,

                get bookUrl() {
                    if (!this.cottageId) return '';
                    return @json(route('book')) + '?cottage_id=' + this.cottageId;
                },

                initWidget() {
                    const self = this;
                    const boot = () => {
                        this.$nextTick(() => {
                            if (initialised) return;
                            initialised = true;
                            fpIn = flatpickr(this.$refs.checkIn, {
                                dateFormat: 'Y-m-d',
                                minDate: 'today',
                                onChange: function (selectedDates, dateStr) {
                                    self.checkIn = dateStr;
                                    self.result = null;
                                    if (fpOut) fpOut.set('minDate', self.shiftDate(dateStr, 1));
                                    self.check();
                                },
                            });
                            fpOut = flatpickr(this.$refs.checkOut, {
                                dateFormat: 'Y-m-d',
                                minDate: 'today',
                                onChange: function (selectedDates, dateStr) {
                                    self.checkOut = dateStr;
                                    self.result = null;
                                    self.check();
                                },
                            });
                        });
                    };

                    // flatpickr.js is a deferred ES module so it may register
                    // after Alpine already fired x-init; poll until it exists.
                    if (typeof window.flatpickr === 'function') {
                        boot();
                    } else {
                        const timer = window.setInterval(() => {
                            if (typeof window.flatpickr === 'function') {
                                window.clearInterval(timer);
                                boot();
                            }
                        }, 50);
                    }
                },

                openCheckIn() { if (fpIn) fpIn.open(); },
                openCheckOut() { if (fpOut) fpOut.open(); },

                shiftDate(dateStr, days) {
                    const d = new Date(dateStr + 'T00:00:00');
                    d.setDate(d.getDate() + days);
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return y + '-' + m + '-' + day;
                },

                todayStr() {
                    const d = new Date();
                    return this.shiftDate(
                        d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'),
                        0
                    );
                },

                onTypeChange() {
                    this.checkIn = '';
                    this.checkOut = '';
                    this.result = null;
                    if (fpIn) fpIn.clear();
                    if (fpOut) { fpOut.set('minDate', 'today'); fpOut.clear(); }
                },

                onCottageChange() {
                    this.checkIn = '';
                    this.checkOut = '';
                    this.result = null;
                    if (fpIn) { fpIn.set('disable', []); fpIn.clear(); }
                    if (fpOut) { fpOut.set('disable', []); fpOut.set('minDate', 'today'); fpOut.clear(); }
                    this.loadBlockedDates();
                },

                // Disable every date the cottage can't take for the next 6
                // months so the pickers guide the guest to real availability.
                loadBlockedDates() {
                    if (!this.cottageId || !fpIn) return;
                    const params = new URLSearchParams({
                        cottage_id: this.cottageId,
                        booking_type: 'day_tour',
                        check_in: this.todayStr(),
                        check_out: this.shiftDate(this.todayStr(), 180),
                    });
                    fetch(endpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                        .then((res) => res.json())
                        .then((data) => {
                            const blocked = data.blocked_dates || [];
                            if (fpIn) fpIn.set('disable', blocked);
                            if (fpOut) fpOut.set('disable', blocked);
                        })
                        .catch(() => {});
                },

                check() {
                    if (!this.cottageId) { this.result = null; return; }
                    if (this.bookingType === 'overnight') {
                        if (!this.checkIn || !this.checkOut) { this.result = null; return; }
                    } else if (!this.checkIn) {
                        this.result = null;
                        return;
                    }

                    if (this.busy) return;
                    this.busy = true;

                    const params = new URLSearchParams({
                        cottage_id: this.cottageId,
                        booking_type: this.bookingType,
                        check_in: this.checkIn,
                    });
                    if (this.checkOut) params.set('check_out', this.checkOut);

                    fetch(endpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                        .then((res) => res.json())
                        .then((data) => { this.result = data; })
                        .catch(() => { this.result = null; })
                        .finally(() => { this.busy = false; });
                },
            };
        }
        </script>
        @endpush
        @endsection
