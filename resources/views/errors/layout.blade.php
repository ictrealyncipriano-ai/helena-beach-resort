@extends('layouts.app')

@section('title', trim($__env->yieldContent('code') . ' ' . $__env->yieldContent('heading')))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <div class="bg-gradient-to-r @yield('gradient', 'from-teal-600 to-teal-700') text-white px-8 py-6 rounded-lg shadow-lg mb-8">
            <h1 class="text-7xl font-bold mb-2">@yield('code')</h1>
            <p class="text-xl font-semibold">@yield('heading')</p>
        </div>
        <p class="text-gray-600 dark:text-slate-300 mb-8 text-lg">
            @yield('message')
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}"
               class="inline-block bg-teal-700 hover:bg-teal-800 text-white font-semibold px-8 py-3 rounded-lg transition-colors">
                Back to Home
            </a>
            @hasSection('extra-actions')
                @yield('extra-actions')
            @endif
        </div>
    </div>
</div>
@endsection
