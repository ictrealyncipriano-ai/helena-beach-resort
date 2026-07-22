@extends('layouts.app')

@section('title', $cottage->name)
@section('description', strip_tags($cottage->description))

@if($cottage->primaryPhoto)
@section('og_image', Storage::url($cottage->primaryPhoto->photo_path))
@endif

@section('og_type', 'article')

@section('content')
<section class="pt-32 pb-8 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('cottages.index') }}" class="inline-flex items-center text-teal-200 hover:text-white text-sm mb-4 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Cottages
        </a>
        <h1 class="text-3xl sm:text-4xl font-bold text-white">{{ $cottage->name }}</h1>
    </div>
</section>

<section class="py-8 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                @if($cottage->photos->isNotEmpty())
                <div class="grid grid-cols-2 gap-4">
                    @foreach($cottage->photos as $photo)
                    <div class="aspect-[4/3] rounded-xl overflow-hidden bg-teal-50">
                        <img src="{{ Storage::url($photo->photo_path) }}" alt="{{ $cottage->name }}" class="w-full h-full object-cover" loading="lazy">
                    </div>
                    @endforeach
                </div>
                @else
                <div class="aspect-video rounded-xl bg-teal-50 flex items-center justify-center text-teal-300 text-8xl">🏠</div>
                @endif

                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">About this Cottage</h2>
                    <div class="prose prose-teal max-w-none text-gray-600">
                        {!! $cottage->description !!}
                    </div>
                </div>

                @if($cottage->amenities->isNotEmpty())
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Amenities</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($cottage->amenities as $amenity)
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 rounded-lg text-sm text-gray-700">
                            <span>{{ $amenity->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">
                        @if($cottage->rate_daytour)
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                            <span class="text-gray-600">Day Tour Rate</span>
                            <span class="text-xl font-bold text-teal-600">₱{{ number_format($cottage->rate_daytour) }}</span>
                        </div>
                        @endif
                        @if($cottage->rate_overnight)
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                            <span class="text-gray-600">Overnight Rate</span>
                            <span class="text-xl font-bold text-teal-600">₱{{ number_format($cottage->rate_overnight) }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                            <span class="text-gray-600">Capacity</span>
                            <span class="font-medium text-gray-900">Up to {{ $cottage->capacity }} guests</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Status</span>
                            @if($cottage->is_available)
                            <span class="text-sm font-medium text-green-600 bg-green-50 px-3 py-1 rounded-full">Available</span>
                            @else
                            <span class="text-sm font-medium text-red-600 bg-red-50 px-3 py-1 rounded-full">Unavailable</span>
                            @endif
                        </div>
                        <a href="{{ route('contact') }}?cottage_id={{ $cottage->id }}" class="block w-full text-center px-6 py-3 bg-teal-600 text-white font-medium rounded-full hover:bg-teal-700 transition-colors">
                            Book This Cottage
                        </a>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Availability Calendar</h3>
                        <div x-data="calendar('{{ json_encode($blockedDates) }}')">
                            <div class="flex items-center justify-between mb-3">
                                <button @click="prevMonth" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="text-sm font-medium text-gray-700" x-text="monthLabel"></span>
                                <button @click="nextMonth" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-0 text-center mb-1">
                                <template x-for="day in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="day">
                                    <span class="text-xs font-medium text-gray-400 py-1" x-text="day"></span>
                                </template>
                            </div>
                            <div class="grid grid-cols-7 gap-0">
                                <template x-for="(day, i) in days" :key="i">
                                    <div class="text-sm py-1.5 rounded-lg"
                                        :class="{
                                            'text-gray-300': !day,
                                            'text-gray-900': day && !day.blocked,
                                            'text-red-400 line-through': day && day.blocked,
                                            'text-gray-400': day && day.isPast
                                        }"
                                        x-text="day ? day.label : ''">
                                    </div>
                                </template>
                            </div>
                            <div class="mt-3 flex items-center gap-3 text-xs text-gray-500">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-red-100 border border-red-200"></span> Booked</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-green-100 border border-green-200"></span> Available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
    function calendar(blockedJson) {
        const blocked = JSON.parse(blockedJson || '[]');
        const today = new Date();
        today.setHours(0,0,0,0);

        return {
            year: today.getFullYear(),
            month: today.getMonth(),

            get monthLabel() {
                return new Date(this.year, this.month).toLocaleString('default', { month: 'long', year: 'numeric' });
            },

            get days() {
                const firstDay = new Date(this.year, this.month, 1).getDay();
                const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                const grid = [];
                for (let i = 0; i < firstDay; i++) grid.push(null);
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateStr = `${this.year}-${String(this.month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const dateObj = new Date(this.year, this.month, d);
                    grid.push({
                        label: d,
                        date: dateStr,
                        blocked: blocked.includes(dateStr),
                        isPast: dateObj < today,
                    });
                }
                return grid;
            },

            prevMonth() {
                if (this.month === 0) { this.month = 11; this.year--; }
                else { this.month--; }
            },

            nextMonth() {
                if (this.month === 11) { this.month = 0; this.year++; }
                else { this.month++; }
            },
        };
    }
</script>
@endpush

@endsection
