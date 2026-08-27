@extends('layouts.app')

@section('title', 'Modify Booking')
@section('description', 'Change the dates, cottage, or schedule of your booking at Helena Beach Resort.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Modify Booking</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Reference: <span class="font-mono font-semibold text-white">{{ $inquiry->reference_code }}</span></p>
    </div>
</section>

<section class="py-16 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div x-data="modifyForm()" x-init="initFlatpickr()" class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            {{-- Form --}}
            <div class="lg:col-span-3">
                <form method="POST" action="{{ route('booking.portal.modify.update', $inquiry) }}" class="space-y-6" x-on:submit="submitting = true">
                    @csrf
                    @method('PUT')

                    <div class="rounded-2xl border border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 p-4 text-sm text-gray-600 dark:text-slate-300 flex items-start gap-2">
                        <x-icons name="info" class="w-4 h-4 shrink-0 mt-0.5 text-teal-700 dark:text-teal-300" />
                        <p>Change any of the details below. Your current schedule stays reserved until you submit the change.</p>
                    </div>

                    {{-- Booking Type --}}
                    <fieldset>
                        <legend class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-3">Type of Booking <span class="text-red-600">*</span></legend>
                        <div class="flex gap-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="booking_type" value="day_tour"
                                    x-model="bookingType" required
                                    aria-required="true"
                                    class="sr-only peer">
                                <div class="text-center px-4 py-3 border-2 border-gray-200 dark:border-slate-700 rounded-xl peer-checked:border-teal-700 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/30 peer-checked:text-teal-700 dark:peer-checked:text-teal-300 transition-colors">
                                    <div class="text-2xl mb-1">☀️</div>
                                    <div class="font-medium text-sm">Day Tour</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">8 AM - 6 PM</div>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="booking_type" value="overnight"
                                    x-model="bookingType" required
                                    aria-required="true"
                                    class="sr-only peer">
                                <div class="text-center px-4 py-3 border-2 border-gray-200 dark:border-slate-700 rounded-xl peer-checked:border-teal-700 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/30 peer-checked:text-teal-700 dark:peer-checked:text-teal-300 transition-colors">
                                    <div class="text-2xl mb-1">🌙</div>
                                    <div class="font-medium text-sm">Overnight</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">Check-in after 2 PM</div>
                                </div>
                            </label>
                        </div>
                        @error('booking_type') <p id="booking-type-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </fieldset>

                    {{-- Cottage --}}
                    <div>
                        <label for="cottage_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Select Cottage <span class="text-red-600">*</span></label>
                        <select id="cottage_id" name="cottage_id" x-model="cottageId" required
                            aria-invalid="{{ $errors->has('cottage_id') ? 'true' : 'false' }}"
                            @error('cottage_id') aria-describedby="cottage-id-error" @enderror
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('cottage_id') border-red-400 @enderror">
                            <option value="">Choose a cottage</option>
                            @foreach($cottages as $cottage)
                            <option value="{{ $cottage->id }}" {{ $cottage->id === $inquiry->cottage_id ? 'selected' : '' }}>
                                {{ $cottage->name }} — {{ formatPrice($cottage->rate_daytour) }} day / {{ formatPrice($cottage->rate_overnight) }} night
                            </option>
                            @endforeach
                        </select>
                        @error('cottage_id') <p id="cottage-id-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="check_in" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Check-in <span class="text-red-600">*</span></label>
                            <input type="text" id="check_in" name="check_in" x-ref="checkIn" x-model="checkIn"
                                inputmode="numeric" autocomplete="off"
                                aria-invalid="{{ $errors->has('check_in') ? 'true' : 'false' }}"
                                @error('check_in') aria-describedby="check-in-error" @enderror
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('check_in') border-red-400 @enderror"
                                placeholder="YYYY-MM-DD or pick a date">
                            @error('check_in') <p id="check-in-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div x-show="bookingType === 'overnight'">
                            <label for="check_out" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Check-out <span class="text-red-600">*</span></label>
                            <input type="text" id="check_out" name="check_out" x-ref="checkOut" x-model="checkOut"
                                inputmode="numeric" autocomplete="off"
                                aria-invalid="{{ $errors->has('check_out') ? 'true' : 'false' }}"
                                @error('check_out') aria-describedby="check-out-error" @enderror
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('check_out') border-red-400 @enderror"
                                placeholder="YYYY-MM-DD or pick a date">
                            @error('check_out') <p id="check-out-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Pax --}}
                    <div>
                        <label for="pax" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Number of Guests <span class="text-red-600">*</span></label>
                        <input type="number" id="pax" name="pax" x-model.number="pax" min="1" max="50" required
                            aria-invalid="{{ $errors->has('pax') ? 'true' : 'false' }}"
                            @error('pax') aria-describedby="pax-error" @enderror
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('pax') border-red-400 @enderror">
                        @error('pax') <p id="pax-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button type="submit" :disabled="submitting"
                            :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:bg-teal-700 transition-colors'"
                            class="px-8 py-3 bg-teal-700 text-white font-medium rounded-full inline-flex items-center justify-center gap-2">
                            <span x-show="submitting" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" x-cloak></span>
                            <span x-text="submitting ? 'Saving…' : 'Save Changes'"></span>
                        </button>
                        <a href="{{ route('booking.portal.show', $inquiry) }}"
                            class="px-8 py-3 border-2 border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-300 font-medium rounded-full hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-colors text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            {{-- Summary Sidebar --}}
            <div class="lg:col-span-2">
                <div class="sticky top-24">
                    <div class="bg-teal-50 dark:bg-teal-900/30 rounded-2xl p-6 space-y-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white text-lg">New Booking Summary</h3>

                        <div class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                            <span class="text-sm text-gray-600 dark:text-slate-300">Cottage</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedCottageName"></span>
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                            <span class="text-sm text-gray-600 dark:text-slate-300">Type</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="bookingType === 'day_tour' ? 'Day Tour' : 'Overnight'"></span>
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                            <span class="text-sm text-gray-600 dark:text-slate-300">Guests</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="pax"></span>
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-teal-100">
                            <span class="text-sm text-gray-600 dark:text-slate-300">Check-in</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="checkIn || '—'"></span>
                        </div>

                        <div x-show="bookingType === 'overnight'" class="flex items-center justify-between pb-3 border-b border-teal-100">
                            <span class="text-sm text-gray-600 dark:text-slate-300">Check-out</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="checkOut || '—'"></span>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-base font-semibold text-gray-900 dark:text-white">New Total</span>
                            <span class="text-xl font-bold text-teal-700 dark:text-teal-300" x-text="totalDisplay"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
@vite('resources/js/flatpickr.js')
<script>
function modifyForm() {
    const blockedData = @js($blockedByCottage);
    const rateData = @js($rates);

    return {
        bookingType: @js($inquiry->booking_type ?? 'day_tour'),
        cottageId: '{{ $inquiry->cottage_id }}',
        checkIn: '{{ $inquiry->check_in?->format('Y-m-d') }}',
        checkOut: '{{ $inquiry->check_out?->format('Y-m-d') }}',
        pax: @js((int) $inquiry->pax),
        fpIn: null,
        fpOut: null,
        submitting: false,

        get selectedCottageName() {
            return this.cottageId && rateData[this.cottageId] ? rateData[this.cottageId].name : '';
        },

        isPeakDate(r, dateStr) {
            if (!r.peak_start || !r.peak_end || !dateStr) return false;
            const md = dateStr.slice(5);
            const start = r.peak_start;
            const end = r.peak_end;
            if (start <= end) return md >= start && md <= end;
            return md >= start || md <= end;
        },

        rateFor(r, type, dateStr) {
            if (this.isPeakDate(r, dateStr)) {
                const peak = type === 'day_tour' ? r.peak_day_tour : r.peak_overnight;
                if (peak && peak > 0) return peak;
            }
            return type === 'day_tour' ? r.day_tour : r.overnight;
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
                return this.checkIn
                    ? '₱' + Number(this.rateFor(r, 'day_tour', this.checkIn)).toLocaleString()
                    : '₱' + Number(r.day_tour).toLocaleString();
            }
            const nights = Math.max(this.nights, 1);
            let total = 0;
            if (this.checkIn) {
                for (let i = 0; i < nights; i++) {
                    const d = this.addDays(this.checkIn, i);
                    total += Number(this.rateFor(r, 'overnight', d));
                }
            } else {
                total = Number(r.overnight) * nights;
            }
            return '₱' + total.toLocaleString();
        },

        get blockedDates() {
            return this.cottageId && blockedData[this.cottageId] ? blockedData[this.cottageId] : [];
        },

        addDays(dateStr, days) {
            const d = new Date(dateStr + 'T00:00:00');
            d.setDate(d.getDate() + days);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },

        initFlatpickr() {
            const self = this;

            const boot = () => {
                this.$nextTick(() => {
                    this.fpIn = flatpickr(this.$refs.checkIn, {
                        dateFormat: 'Y-m-d',
                        minDate: 'today',
                        // Typed dates keep the form keyboard-operable (WCAG 2.1.1).
                        allowInput: true,
                        disable: this.blockedDates,
                        onChange: function(selectedDates, dateStr) {
                            self.checkIn = dateStr;
                            if (self.fpOut) {
                                self.fpOut.set('minDate', self.addDays(dateStr, 1));
                            }
                        },
                    });

                    this.fpOut = flatpickr(this.$refs.checkOut, {
                        dateFormat: 'Y-m-d',
                        minDate: 'today',
                        allowInput: true,
                        disable: this.blockedDates,
                        onChange: function(selectedDates, dateStr) {
                            self.checkOut = dateStr;
                        },
                    });

                    if (this.checkIn) this.fpIn.setDate(this.checkIn);
                    if (this.checkOut) this.fpOut.setDate(this.checkOut);
                });
            };

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

        init() {
            this.$watch('cottageId', () => {
                if (this.fpIn) {
                    this.fpIn.set('disable', this.blockedDates);
                    this.fpIn.clear();
                    this.checkIn = '';
                }
                if (this.fpOut) {
                    this.fpOut.set('disable', this.blockedDates);
                    this.fpOut.set('minDate', 'today');
                    this.fpOut.clear();
                    this.checkOut = '';
                }
            });

            this.$watch('bookingType', () => {
                if (this.fpIn) this.fpIn.clear();
                if (this.fpOut) {
                    this.fpOut.set('minDate', 'today');
                    this.fpOut.clear();
                }
                this.checkIn = '';
                this.checkOut = '';
            });
        },
    };
}
</script>
@endpush
@endsection