@extends('errors.layout')

@section('code', '429')
@section('heading', 'Too Many Requests')
@section('gradient', 'from-amber-500 to-amber-600')
@section('message', "You've made too many requests. Please wait a moment before trying again.")

@section('extra-actions')
<a href="#" onclick="history.back(); return false;"
   class="inline-block bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 font-semibold px-8 py-3 rounded-lg transition-colors">
    Go Back and Try Again Shortly
</a>
@endsection
