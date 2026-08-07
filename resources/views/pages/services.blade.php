@extends('layouts.app')

@section('title', 'Amenities & Services')
@section('description', 'Explore the amenities and services offered at Helena Beach Resort in Infanta, Quezon.')

@section('content')
<x-hero title="Amenities & Services" subtitle="Everything you need for a memorable beach getaway." />

<section class="py-20 sm:py-28 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($services->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-6 text-gray-500 dark:text-slate-400">
                <x-icons name="grid" class="w-8 h-8" />
            </div>
            <h2 class="text-xl font-semibold text-gray-600 dark:text-slate-300">Services coming soon</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-2">We're adding more information about our amenities.</p>
        </div>
        @else
        @foreach($services as $category => $items)
        <div class="mb-16 last:mb-0 reveal">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2 font-heading">{{ $category }}</h2>
            <div class="w-16 h-1 bg-teal-500 rounded-full mb-8"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($items as $service)
                <div class="group bg-white dark:bg-slate-800 rounded-2xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-teal-100 transition-all duration-300">
                    <div class="w-12 h-12 bg-teal-50 dark:bg-teal-900/30 rounded-xl flex items-center justify-center text-teal-700 dark:text-teal-300 mb-4 group-hover:bg-teal-100 dark:group-hover:bg-teal-900/50 transition-colors">
                        @if($service->icon === '🏖️')
                        <x-icons name="sun" class="w-6 h-6" />
                        @elseif($service->icon === '🅿️')
                        <x-icons name="parking" class="w-6 h-6" />
                        @elseif($service->icon === '❄️')
                        <x-icons name="snow" class="w-6 h-6" />
                        @elseif($service->icon === '🚿')
                        <x-icons name="cloud" class="w-6 h-6" />
                        @elseif($service->icon === '🍽️')
                        <x-icons name="food" class="w-6 h-6" />
                        @elseif($service->icon === '🔥')
                        <x-icons name="fire" class="w-6 h-6" />
                        @elseif($service->icon === '🎤')
                        <x-icons name="mic" class="w-6 h-6" />
                        @elseif($service->icon === '🏐')
                        <x-icons name="volleyball" class="w-6 h-6" />
                        @elseif($service->icon === '🚤')
                        <x-icons name="boat" class="w-6 h-6" />
                        @elseif($service->icon === '🏛️')
                        <x-icons name="building" class="w-6 h-6" />
                        @elseif($service->icon === '🤝')
                        <x-icons name="users" class="w-6 h-6" />
                        @else
                        <x-icons name="sparkles" class="w-6 h-6" />
                        @endif
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $service->name }}</h3>
                    @if($service->description)
                    <p class="text-sm text-gray-600 dark:text-slate-300 leading-relaxed">{{ $service->description }}</p>
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
