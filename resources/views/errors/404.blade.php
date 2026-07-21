@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white px-8 py-6 rounded-lg shadow-lg mb-8">
            <h1 class="text-7xl font-bold mb-2">404</h1>
            <p class="text-xl font-semibold">Page Not Found</p>
        </div>
        <p class="text-gray-600 mb-8 text-lg">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <a href="{{ route('home') }}"
           class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors">
            Back to Home
        </a>
    </div>
</div>
@endsection
