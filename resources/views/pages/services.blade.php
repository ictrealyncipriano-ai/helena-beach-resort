@extends('layouts.app')

@section('title', 'Amenities & Services')
@section('description', 'Explore the amenities and services offered at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Amenities & Services</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Everything you need for a memorable beach getaway.</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($services->isEmpty())
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🏗️</div>
            <h2 class="text-xl font-semibold text-gray-600">Services coming soon</h2>
            <p class="text-gray-400 mt-2">We're adding more information about our amenities.</p>
        </div>
        @else
        @foreach($services as $category => $items)
        <div class="mb-16 last:mb-0">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $category }}</h2>
            <div class="w-16 h-1 bg-teal-500 rounded-full mb-8"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($items as $service)
                <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-md transition-shadow">
                    <div class="text-4xl mb-4">{{ $service->icon }}</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $service->name }}</h3>
                    @if($service->description)
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $service->description }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
        @endif
    </div>
</section>
@endsection
