@extends('layouts.app')

@section('title', 'Book Your Stay')
@section('description', 'Book your stay at Helena Beach Resort in Infanta, Quezon.')
@section('canonical', route('book'))

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Book Your Stay</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto mb-6">Select your cottage, choose your dates, and send us your booking request.</p>
        <div class="inline-flex items-center gap-2 sm:gap-3 text-sm text-teal-100 bg-teal-800/50 backdrop-blur-sm rounded-full px-5 py-2.5 font-medium">
            <span>1 <span class="font-semibold text-white">Request</span></span>
            <x-icons name="chevron-right" class="w-3.5 h-3.5 opacity-60" />
            <span>2 <span class="font-semibold text-white">We confirm</span></span>
            <x-icons name="chevron-right" class="w-3.5 h-3.5 opacity-60" />
            <span>3 <span class="font-semibold text-white">You pay</span></span>
        </div>
    </div>
</section>

<section class="py-16 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div x-data="bookingForm()" x-init="initFlatpickr()" class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            {{-- Form --}}
            <div class="lg:col-span-3">
                <form method="POST" action="{{ route('book.store') }}" class="space-y-6" x-on:submit="submitting = true">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Name <span class="text-red-600">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                @error('name') aria-describedby="name-error" @enderror
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('name') border-red-400 @enderror">
                            @error('name') <p id="name-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Email <span class="text-red-600">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                @error('email') aria-describedby="email-error" @enderror
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('email') border-red-400 @enderror">
                            @error('email') <p id="email-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Phone</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm">
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
                            <option value="{{ $cottage->id }}" {{ old('cottage_id') == $cottage->id ? 'selected' : '' }}>
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
                    <p x-show="notice" x-text="notice" id="date-notice" role="status" x-cloak class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2"></p>

                    {{-- Pax --}}
                    <div>
                        <label for="pax" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Number of Guests <span class="text-red-600">*</span></label>
                        <input type="number" id="pax" name="pax" x-model.number="pax" min="1" max="50" value="{{ old('pax', 1) }}" required
                            aria-invalid="{{ $errors->has('pax') ? 'true' : 'false' }}"
                            @error('pax') aria-describedby="pax-error" @enderror
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm @error('pax') border-red-400 @enderror">
                        @error('pax') <p id="pax-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Message --}}
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Special Requests</label>
                        <textarea id="message" name="message" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm">{{ old('message') }}</textarea>
                    </div>

                    {{-- Promo code --}}
                    <div>
                        <label for="promo_code" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Promo Code</label>
                        <input type="text" id="promo_code" name="promo_code" maxlength="50" placeholder="e.g. SUMMER10"
                            value="{{ old('promo_code') }}"
                            aria-invalid="{{ $errors->has('promo_code') ? 'true' : 'false' }}"
                            @error('promo_code') aria-describedby="promo-code-error" @enderror
                            x-model="promoCode"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-colors text-sm uppercase @error('promo_code') border-red-400 @enderror">
                        @error('promo_code') <p id="promo-code-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" :disabled="submitting"
                        :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:bg-teal-800 transition-colors'"
                        class="w-full sm:w-auto px-8 py-3 min-h-[44px] bg-teal-700 text-white font-medium rounded-full inline-flex items-center justify-center gap-2">
                        <span x-show="submitting" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" x-cloak></span>
                        <span x-text="submitting ? 'Submitting…' : 'Submit Booking Request'"></span>
                    </button>
                    <p class="text-xs text-gray-500 dark:text-slate-400">No payment now · We confirm within 24h · 48h hold · Free cancellation · <a href="{{ route('contact') }}" class="underline underline-offset-2 hover:text-teal-700">Questions? Contact us</a> · <a href="{{ route('booking-policy') }}" class="underline underline-offset-2 hover:text-teal-700">Booking Policy</a></p>
                </form>
            </div>

            {{-- Summary Sidebar --}}
            <div class="lg:col-span-2">
                <div class="sticky top-24">
                    <div class="bg-teal-50 dark:bg-teal-900/30 rounded-2xl p-6 space-y-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Booking Summary</h3>

                        <template x-if="!cottageId">
                            <p class="text-sm text-gray-500 dark:text-slate-400">Select a cottage to see the summary.</p>
                        </template>

                        <template x-if="cottageId">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Cottage</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedCottageName"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Type</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="bookingType === 'day_tour' ? 'Day Tour' : 'Overnight'"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Guests</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="pax"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Check-in</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="checkIn || '—'"></span>
                                </div>

                                <div x-show="bookingType === 'overnight'" class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Check-out</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="checkOut || '—'"></span>
                                </div>

                                <div x-show="bookingType === 'overnight'" class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Nights</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="nights"></span>
                                </div>

                                <div class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Rate</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="rateLabel"></span>
                                </div>

                                <div x-show="promoCode" class="flex items-center justify-between pb-3 border-b border-teal-100 dark:border-teal-700">
                                    <span class="text-sm text-gray-600 dark:text-slate-300">Promo</span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium dark:bg-amber-900/40 dark:text-amber-300">
                                        <span x-text="promoCode.toUpperCase()"></span>
                                        <span>applied at confirmation</span>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-base font-semibold text-gray-900 dark:text-white">Total</span>
                                    <span class="text-xl font-bold text-teal-700 dark:text-teal-300" x-text="totalDisplay"></span>
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
@vite('resources/js/flatpickr.js')
<script>
function bookingForm() {
    const blockedData = @js($blockedByCottage);
    const rateData = @js($rates);

    return {
        bookingType: 'day_tour',
        cottageId: '{{ old('cottage_id', $prefillCottageId ?? '') }}',
        checkIn: '{{ old('check_in') }}',
        checkOut: '{{ old('check_out') }}',
        pax: @js((int) old('pax', 1)),
        promoCode: '{{ old('promo_code') }}',
        fpIn: null,
        fpOut: null,
        submitting: false,
        notice: '',

        get selectedCottageName() {
            return this.cottageId && rateData[this.cottageId] ? rateData[this.cottageId].name : '';
        },

        get rateLabel() {
            if (!this.cottageId || !rateData[this.cottageId]) return '—';
            const r = rateData[this.cottageId];
            return this.bookingType === 'day_tour'
                ? '₱' + Number(this.rateFor(r, 'day_tour', this.checkIn)).toLocaleString() + ' / day'
                : '₱' + Number(this.rateFor(r, 'overnight', this.checkIn)).toLocaleString() + ' / night';
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
                return '₱' + Number(this.rateFor(r, 'day_tour', this.checkIn)).toLocaleString();
            }
            // Overnight always shows at least the 1-night minimum so the
            // summary never shows a bare "—" (same-day stays are rejected
            // server-side anyway).
            const nights = Math.max(this.nights, 1);
            let total = 0;
            if (this.checkIn) {
                for (let i = 0; i < nights; i++) {
                    const d = this.addDays(this.checkIn, i);
                    total += Number(this.rateFor(r, 'overnight', d));
                }
            } else {
                total = Number(this.rateFor(r, 'overnight', null)) * nights;
            }
            return '₱' + total.toLocaleString();
        },

        get blockedDates() {
            return this.cottageId && blockedData[this.cottageId] ? blockedData[this.cottageId] : [];
        },

        get blockedSet() {
            return new Set(this.blockedDates);
        },

        addDays(dateStr, days) {
            const d = new Date(dateStr + 'T00:00:00');
            d.setDate(d.getDate() + days);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },

        isBlocked(dateStr) {
            return !!dateStr && this.blockedSet.has(dateStr);
        },

        refreshDisable() {
            if (this.fpIn) this.fpIn.set('disable', this.blockedDates);
            if (this.fpOut) this.fpOut.set('disable', this.blockedDates);
        },

        initFlatpickr() {
            const self = this;

            // flatpickr.js is a deferred ES module at the end of <body>, so it
            // runs AFTER app.js has already called Alpine.start() (which fires
            // x-init synchronously). window.flatpickr may not exist yet, so
            // poll until the module registers it before binding the pickers.
            const boot = () => {
                this.$nextTick(() => {
                    this.fpIn = flatpickr(this.$refs.checkIn, {
                        dateFormat: 'Y-m-d',
                        minDate: 'today',
                        // Keyboard users must be able to type a date directly
                        // (WCAG 2.1.1); the picker remains available via click
                        // or Enter, and typed values are parsed on blur/Enter.
                        allowInput: true,
                        disable: this.blockedDates,
                        onChange: function(selectedDates, dateStr) {
                            self.notice = '';
                            self.checkIn = dateStr;
                            if (self.fpOut) {
                                // Same-day checkout is not allowed: the earliest
                                // check-out is the day after check-in.
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
                            self.notice = '';
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
                this.notice = '';
                this.refreshDisable();
                const name = this.selectedCottageName || 'this cottage';
                if (this.checkIn) {
                    if (this.isBlocked(this.checkIn)) {
                        if (this.fpIn) this.fpIn.clear();
                        this.checkIn = '';
                        this.notice = `That check-in date is blocked for ${name} — please pick another.`;
                        if (this.$refs.checkIn) this.$refs.checkIn.focus();
                    } else if (this.fpIn) {
                        this.fpIn.setDate(this.checkIn, false);
                    }
                }
                if (this.checkOut) {
                    const invalidOut = this.isBlocked(this.checkOut)
                        || (this.checkIn && this.checkOut <= this.checkIn);
                    if (invalidOut) {
                        if (this.fpOut) this.fpOut.clear();
                        this.checkOut = '';
                        if (!this.notice) this.notice = `That check-out date is not available for ${name} — please pick another.`;
                    } else if (this.fpOut) {
                        this.fpOut.setDate(this.checkOut, false);
                    }
                }
                if (this.fpOut) {
                    this.fpOut.set('minDate', this.checkIn ? this.addDays(this.checkIn, 1) : 'today');
                }
            });

            this.$watch('bookingType', () => {
                this.notice = '';
                this.refreshDisable();
                if (this.bookingType === 'day_tour') {
                    if (this.fpOut) {
                        this.fpOut.set('minDate', 'today');
                        this.fpOut.clear();
                    }
                    this.checkOut = '';
                } else if (this.checkOut && this.checkIn && this.checkOut <= this.checkIn) {
                    if (this.fpOut) this.fpOut.clear();
                    this.checkOut = '';
                    this.notice = 'Please choose a check-out date after check-in for overnight stays.';
                }
            });
        },
    };
}
</script>
@endpush
@endsection
