@extends('layouts.app')

@section('title', 'Too Many Requests')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white px-8 py-6 rounded-lg shadow-lg mb-8">
            <h1 class="text-7xl font-bold mb-2">429</h1>
            <p class="text-xl font-semibold">Too Many Requests</p>
        </div>
        <p class="text-gray-600 dark:text-slate-300 mb-8 text-lg">
            You've made too many requests. Please wait a moment before trying again.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}"
               class="inline-block bg-teal-700 hover:bg-teal-800 text-white font-semibold px-8 py-3 rounded-lg transition-colors">
                Back to Home
            </a>
            <a href="#" onclick="history.back(); return false;"
               class="inline-block bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 font-semibold px-8 py-3 rounded-lg transition-colors">
                Go Back and Try Again Shortly
            </a>
        </div>
    </div>
</div>
@endsection
