@extends('layouts.app')

@section('title', $title)
@section('description', $title.' — '.($site['name'] ?? config('app.name')).', '.($site['geo_placename'] ?? ''))
@section('canonical', url()->current())

@section('content')
<x-hero title="{{ $title }}" :subtitle="($site['name'] ?? config('app.name')).' — '.($site['geo_placename'] ?? '')" />

<section class="py-16 sm:py-24 bg-white dark:bg-slate-800">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-teal max-w-none text-gray-600 dark:text-slate-300 leading-relaxed">
            {!! $content !!}
        </div>
    </div>
</section>
@endsection