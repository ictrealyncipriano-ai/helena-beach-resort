@extends('layouts.app')

@section('title', 'Book Your Stay')
@section('description', 'Book your stay at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Book Your Stay</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Select your cottage, choose your dates, and send us your booking request.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div x-data="bookingForm()" x-init="initFlatpickr()" class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            {{-- Form --}}
            <div class="lg:col-span-3">
                <form method="POST" action="{{ route('book.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm @error('name') border-red-400 @enderror">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm @error('email') border-red-400 @enderror">
                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">
                    </div>

                    {{-- Booking Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Type of Booking <span class="text-red-500">*</span></label>
                        <div class="flex gap-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="booking_type" value="day_tour"
                                    x-model="bookingType"
                                    class="sr-only peer">
                                <div class="text-center px-4 py-3 border-2 border-gray-200 rounded-xl peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-700 transition-colors">
                                    <div class="text-2xl mb-1">☀️</div>
                                    <div class="font-medium text-sm">Day Tour</div>
                                    <div class="text-xs text-gray-500">8 AM - 6 PM</div>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="booking_type" value="overnight"
                                    x-model="bookingType"
                                    class="sr-only peer">
                                <div class="text-center px-4 py-3 border-2 border-gray-200 rounded-xl peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-700 transition-colors">
                                    <div class="text-2xl mb-1">🌙</div>
                                    <div class="font-medium text-sm">Overnight</div>
                                    <div class="text-xs text-gray-500">Check-in after 2 PM</div>
                                </div>
                            </label>
                        </div>
                        @error('booking_type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Cottage --}}
                    <div>
                        <label for="cottage_id" class="block text-sm font-medium text-gray-700 mb-1">Select Cottage <span class="text-red-500">*</span></label>
                        <select id="cottage_id" name="cottage_id" x-model="cottageId" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm @error('cottage_id') border-red-400 @enderror">
                            <option value="">Choose a cottage</option>
                            @foreach($cottages as $cottage)
                            <option value="{{ $cottage->id }}" {{ old('cottage_id') == $cottage->id ? 'selected' : '' }}>
                                {{ $cottage->name }} — ₱{{ number_format($cottage->rate_daytour) }} day / ₱{{ number_format($cottage->rate_overnight) }} night
                            </option>
                            @endforeach
                        </select>
                        @error('cottage_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="check_in" class="block text-sm font-medium text-gray-700 mb-1">Check-in <span class="text-red-500">*</span></label>
                            <input type="text" id="check_in" name="check_in" x-ref="checkIn" x-model="checkIn" readonly
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm bg-white @error('check_in') border-red-400 @enderror"
                                placeholder="Select date">
                            @error('check_in') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div x-show="bookingType === 'overnight'">
                            <label for="check_out" class="block text-sm font-medium text-gray-700 mb-1">Check-out <span class="text-red-500">*</span></label>
                            <input type="text" id="check_out" name="check_out" x-ref="checkOut" x-model="checkOut" readonly
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm bg-white @error('check_out') border-red-400 @enderror"
                                placeholder="Select date">
                            @error('check_out') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Pax --}}
                    <div>
                        <label for="pax" class="block text-sm font-medium text-gray-700 mb-1">Number of Guests <span class="text-red-500">*</span></label>
                        <input type="number" id="pax" name="pax" x-model.number="pax" min="1" max="50" value="{{ old('pax', 1) }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm @error('pax') border-red-400 @enderror">
                        @error('pax') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Message --}}
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                        <textarea id="message" name="message" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-teal-600 text-white font-medium rounded-full hover:bg-teal-700 transition-colors">
                        Submit Booking Request
                    </button>
                </form>
            </div>

            {{-- Summary Sidebar --}}
            <div class="lg:col-span-2">
                <div class="sticky top-24">
                    <div class="bg-teal-50 rounded-2xl p-6 space-y-4">
                        <h3 class="font-semibold text-gray-900 text-lg">Booking Summary</h3>

                        <template x-if="!cottageId">
                            <p class="text-sm text-gray-400">Select a cottage to see the summary.</p>
                        </template>

                        <template x-if="cottageId">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Cottage</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="selectedCottageName"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Type</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="bookingType === 'day_tour' ? 'Day Tour' : 'Overnight'"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Guests</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="pax"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Check-in</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="checkIn || '—'"></span>
                                </div>

                                <div x-show="bookingType === 'overnight'" class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Check-out</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="checkOut || '—'"></span>
                                </div>

                                <div x-show="bookingType === 'overnight'" class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Nights</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="nights"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                                    <span class="text-sm text-gray-600">Rate</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="rateLabel"></span>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-base font-semibold text-gray-900">Total</span>
                                    <span class="text-xl font-bold text-teal-600" x-text="totalDisplay"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
function bookingForm() {
    const blockedData = @js($blockedByCottage);
    const rateData = @js($rates);

    return {
        bookingType: 'day_tour',
        cottageId: '{{ old('cottage_id', request('cottage_id')) }}',
        checkIn: '{{ old('check_in') }}',
        checkOut: '{{ old('check_out') }}',
        pax: {{ old('pax', 1) }},
        fpIn: null,
        fpOut: null,

        get selectedCottageName() {
            return this.cottageId && rateData[this.cottageId] ? rateData[this.cottageId].name : '';
        },

        get rateLabel() {
            if (!this.cottageId || !rateData[this.cottageId]) return '—';
            const r = rateData[this.cottageId];
            return this.bookingType === 'day_tour'
                ? '₱' + Number(r.day_tour).toLocaleString() + ' / day'
                : '₱' + Number(r.overnight).toLocaleString() + ' / night';
        },

        get nights() {
            if (!this.checkIn || !this.checkOut) return 0;
            const a = new Date(this.checkIn), b = new Date(this.checkOut);
            return Math.max(0, Math.round((b - a) / (1000 * 60 * 60 * 24)));
        },

        get totalDisplay() {
            if (!this.cottageId || !rateData[this.cottageId]) return '—';
            const r = rateData[this.cottageId];
            if (this.bookingType === 'day_tour') {
                return '₱' + Number(r.day_tour).toLocaleString();
            }
            if (this.nights > 0) {
                return '₱' + (Number(r.overnight) * this.nights).toLocaleString();
            }
            return '—';
        },

        get blockedDates() {
            return this.cottageId && blockedData[this.cottageId] ? blockedData[this.cottageId] : [];
        },

        initFlatpickr() {
            this.$nextTick(() => {
                const self = this;

                this.fpIn = flatpickr(this.$refs.checkIn, {
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    disable: this.blockedDates,
                    onChange: function(selectedDates, dateStr) {
                        self.checkIn = dateStr;
                        if (self.fpOut) {
                            self.fpOut.set('minDate', dateStr);
                        }
                    },
                });

                this.fpOut = flatpickr(this.$refs.checkOut, {
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    disable: this.blockedDates,
                    onChange: function(selectedDates, dateStr) {
                        self.checkOut = dateStr;
                    },
                });

                if (this.checkIn) this.fpIn.setDate(this.checkIn);
                if (this.checkOut) this.fpOut.setDate(this.checkOut);
            });
        },

        init() {
            this.$watch('cottageId', () => {
                if (this.fpIn) {
                    this.fpIn.set('disable', this.blockedDates);
                    this.fpIn.clear();
                    this.checkIn = '';
                }
                if (this.fpOut) {
                    this.fpOut.set('disable', this.blockedDates);
                    this.fpOut.clear();
                    this.checkOut = '';
                }
            });

            this.$watch('bookingType', () => {
                if (this.fpIn) this.fpIn.clear();
                if (this.fpOut) this.fpOut.clear();
                this.checkIn = '';
                this.checkOut = '';
            });
        },
    };
}
</script>
@endpush
@endsection
