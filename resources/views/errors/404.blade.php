@extends('errors.layout')

@section('code', '404')
@section('heading', 'Page Not Found')
@section('message', "The page you're looking for doesn't exist or has been moved.")

@section('extra-actions')
<a href="{{ route('booking.portal.lookup') }}"
   class="inline-block border-2 border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-300 font-semibold px-8 py-3 rounded-lg hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-colors">
    Find My Booking
</a>
@endsection
