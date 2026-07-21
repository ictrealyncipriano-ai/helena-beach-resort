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
                <div class="sticky top-24 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">
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
            </div>
        </div>
    </div>
</section>
@endsection
