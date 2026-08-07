@extends('layouts.app')

@section('title', 'Forbidden')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <div class="bg-gradient-to-r from-red-600 to-red-700 text-white px-8 py-6 rounded-lg shadow-lg mb-8">
            <h1 class="text-7xl font-bold mb-2">403</h1>
            <p class="text-xl font-semibold">Access Forbidden</p>
        </div>
        <p class="text-gray-600 dark:text-slate-300 mb-8 text-lg">
            You don't have permission to view this page.
        </p>
        <a href="{{ route('home') }}"
           class="inline-block bg-teal-700 hover:bg-teal-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors">
            Back to Home
        </a>
    </div>
</div>
@endsection
