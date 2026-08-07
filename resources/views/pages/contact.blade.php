@extends('layouts.app')

@section('title', 'Contact Us')
@section('description', 'Contact Helena Beach Resort to book your stay or ask any questions.')

@section('content')
<x-hero title="Contact Us" subtitle="Send us a message and we'll get back to you as soon as possible." />

<section class="py-20 sm:py-28 bg-white dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-300 text-sm flex items-center gap-2 reveal">
            <x-icons name="check" class="w-5 h-5 shrink-0" />
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            {{-- Form --}}
            <div class="lg:col-span-3 reveal">
                <form method="POST" action="{{ route('contact.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Name <span class="text-red-600">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                @error('name') aria-describedby="name-error" @enderror
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm @error('name') border-red-400 @enderror">
                            @error('name') <p id="name-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Email <span class="text-red-600">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                @error('email') aria-describedby="email-error" @enderror
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm @error('email') border-red-400 @enderror">
                            @error('email') <p id="email-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Phone</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm">
                        </div>
                        <div>
                            <label for="cottage_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Interested Cottage</label>
                            <select id="cottage_id" name="cottage_id" x-on:change="showAvailability"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm">
                                <option value="">Select a cottage</option>
                                @foreach($cottages as $cottage)
                                <option value="{{ $cottage->id }}" {{ old('cottage_id') == $cottage->id || request('cottage_id') == $cottage->id ? 'selected' : '' }}
                                    data-blocked='{{ json_encode($blockedByCottage[$cottage->id] ?? []) }}'>
                                    {{ $cottage->name }}
                                </option>
                                @endforeach
                            </select>
                            <div id="availability-info" class="mt-2 text-xs hidden"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <label for="check_in" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">
                                <x-icons name="calendar" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                                Check-in
                            </label>
                            <input type="date" id="check_in" name="check_in" value="{{ old('check_in') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm">
                        </div>
                        <div>
                            <label for="check_out" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">
                                <x-icons name="calendar" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                                Check-out
                            </label>
                            <input type="date" id="check_out" name="check_out" value="{{ old('check_out') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm">
                        </div>
                        <div>
                            <label for="pax" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">
                                <x-icons name="users" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                                Guests
                            </label>
                            <input type="number" id="pax" name="pax" value="{{ old('pax', 1) }}" min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Message <span class="text-red-600">*</span></label>
                        <textarea id="message" name="message" rows="5" required
                            aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                            @error('message') aria-describedby="message-error" @enderror
                            class="w-full px-4 py-2.5 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-teal-700 focus:border-teal-700 dark:focus:border-teal-700 dark:ring-teal-700/20 outline-none transition-all text-sm @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                        @error('message') <p id="message-error" class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-teal-700 text-white font-medium rounded-full hover:bg-teal-700 transition-all hover:shadow-lg hover:shadow-teal-600/20 active:scale-95 inline-flex items-center gap-2">
                        <x-icons name="email" class="w-4 h-4" />
                        Send Inquiry
                    </button>
                </form>
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-2 space-y-6 reveal reveal-delay-1">
                <div class="bg-gradient-to-br from-teal-50 to-teal-50/50 dark:from-teal-900/30 dark:to-teal-900/20 rounded-2xl p-6 border border-teal-100/50 dark:border-teal-900/30">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center text-teal-700 dark:text-teal-300">
                            <x-icons name="location" class="w-5 h-5" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Location</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-slate-300 leading-relaxed">Purok Buyan, Brgy. Dinahican, Infanta, Quezon</p>
                </div>

                <div class="bg-gradient-to-br from-teal-50 to-teal-50/50 dark:from-teal-900/30 dark:to-teal-900/20 rounded-2xl p-6 border border-teal-100/50 dark:border-teal-900/30">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center text-teal-700 dark:text-teal-300">
                            <x-icons name="phone" class="w-5 h-5" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Contact</h3>
                    </div>
                    @php
                        $contactPhone = trim((string) \App\Models\SiteSetting::getValue('contact_phone', ''));
                    @endphp
                    @if($contactPhone && $contactPhone !== 'N/A')
                    <p class="text-sm text-gray-600 dark:text-slate-300">
                        <a href="tel:{{ $contactPhone }}" class="text-teal-700 dark:text-teal-300 font-medium hover:underline">{{ $contactPhone }}</a>
                    </p>
                    @endif
                </div>

                <div class="bg-gradient-to-br from-teal-50 to-teal-50/50 dark:from-teal-900/30 dark:to-teal-900/20 rounded-2xl p-6 border border-teal-100/50 dark:border-teal-900/30">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center text-teal-700 dark:text-teal-300">
                            <x-icons name="clock" class="w-5 h-5" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Operating Hours</h3>
                    </div>
                    <div class="space-y-1 text-sm text-gray-600 dark:text-slate-300">
                        <p>Monday - Sunday: 8:00 AM - 6:00 PM</p>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Overnight stays available upon reservation</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
    function showAvailability() {
        const select = document.getElementById('cottage_id');
        const info = document.getElementById('availability-info');
        const option = select.options[select.selectedIndex];

        if (!option.value) {
            info.classList.add('hidden');
            return;
        }

        let blocked;
        try { blocked = JSON.parse(option.dataset.blocked || '[]'); } catch { blocked = []; }

        if (blocked.length === 0) {
            info.className = 'mt-2 text-xs text-green-600';
            info.textContent = 'This cottage has no booked dates.';
        } else {
            const dates = blocked.map(d => {
                const [y, m, day] = d.split('-');
                return new Date(y, m - 1, day).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            info.className = 'mt-2 text-xs text-amber-600';
            info.textContent = `Currently booked on: ${dates.join(', ')}`;
        }
        info.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', showAvailability);
    document.getElementById('cottage_id').addEventListener('change', showAvailability);
</script>
@endpush

@endsection
