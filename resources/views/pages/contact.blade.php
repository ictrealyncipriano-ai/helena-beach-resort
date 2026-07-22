@extends('layouts.app')

@section('title', 'Contact Us')
@section('description', 'Contact Helena Beach Resort to book your stay or ask any questions.')

@section('content')
{{-- Header --}}
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Contact Us</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Send us a message and we'll get back to you as soon as possible.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
            {{-- Form --}}
            <div class="lg:col-span-3">
                <form method="POST" action="{{ route('contact.store') }}" class="space-y-6">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">
                        </div>
                        <div>
                            <label for="cottage_id" class="block text-sm font-medium text-gray-700 mb-1">Interested Cottage</label>
                            <select id="cottage_id" name="cottage_id" x-on:change="showAvailability"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">
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
                            <label for="check_in" class="block text-sm font-medium text-gray-700 mb-1">Check-in</label>
                            <input type="date" id="check_in" name="check_in" value="{{ old('check_in') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">
                        </div>
                        <div>
                            <label for="check_out" class="block text-sm font-medium text-gray-700 mb-1">Check-out</label>
                            <input type="date" id="check_out" name="check_out" value="{{ old('check_out') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">
                        </div>
                        <div>
                            <label for="pax" class="block text-sm font-medium text-gray-700 mb-1">Guests</label>
                            <input type="number" id="pax" name="pax" value="{{ old('pax', 1) }}" min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                        <textarea id="message" name="message" rows="5" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-colors text-sm @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                        @error('message') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-teal-600 text-white font-medium rounded-full hover:bg-teal-700 transition-colors">
                        Send Inquiry
                    </button>
                </form>
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-teal-50 rounded-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">📍 Location</h3>
                    <p class="text-sm text-gray-600">Purok Buyan, Brgy. Dinahican, Infanta, Quezon</p>
                </div>

                <div class="bg-teal-50 rounded-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">📞 Contact</h3>
                    <p class="text-sm text-gray-600">Contact number available upon request</p>
                </div>

                <div class="bg-teal-50 rounded-2xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">⏰ Operating Hours</h3>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p>Monday - Sunday: 8:00 AM - 6:00 PM</p>
                        <p class="text-xs text-gray-400 mt-2">Overnight stays available upon reservation</p>
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
