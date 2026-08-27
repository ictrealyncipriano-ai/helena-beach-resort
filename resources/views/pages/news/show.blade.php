@extends('layouts.app')

@section('title', $post->title)
@section('description', $post->excerpt ? Str::limit(strip_tags(html_entity_decode($post->excerpt)), 160) : Str::limit(strip_tags(html_entity_decode($post->body)), 160))
@section('canonical', route('news.show', $post))
@section('og_type', 'article')
@if($post->cover_image)
@section('og_image', Storage::url($post->cover_image))
@section('og_image_alt', $post->title)
@endif

@push('head')
@php
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $post->title,
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'inLanguage' => 'en',
        'mainEntityOfPage' => route('news.show', $post),
        'publisher' => [
            '@type' => 'Organization',
            'name' => App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort'),
        ],
    ];
    if ($post->cover_image) {
        $articleSchema['image'] = Storage::url($post->cover_image);
    }
@endphp
<script type="application/ld+json">
@json($articleSchema)
</script>
@endpush

@section('content')
<header class="relative pt-32 pb-16 overflow-hidden bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <nav class="text-sm text-teal-200/80 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
            <span class="mx-2 text-teal-200/50">›</span>
            <a href="{{ route('news.index') }}" class="hover:text-white transition-colors">News</a>
        </nav>
        <p class="text-sm font-medium text-teal-200 uppercase tracking-wider mb-3">
            {{ $post->published_at->format('F j, Y') }}
        </p>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white font-heading">{{ $post->title }}</h1>
    </div>
    <div class="absolute bottom-0 left-0 right-0 leading-none">
        <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <path d="M0 20C240 40 480 40 720 28C960 16 1200 16 1440 28V40H0V20Z" fill="white"/>
        </svg>
    </div>
</header>

<article class="py-16 sm:py-20 bg-white dark:bg-slate-800">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($post->cover_image)
        <img src="{{ Storage::url($post->cover_image) }}" alt="{{ $post->title }}" width="768" height="432" class="w-full aspect-[16/9] object-cover rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm mb-10">
        @endif

        @if($post->excerpt)
        <p class="text-lg text-gray-700 dark:text-slate-200 font-medium leading-relaxed mb-6">
            {{ $post->excerpt }}
        </p>
        @endif

        <div class="prose prose-teal max-w-none dark:prose-invert prose-headings:font-heading prose-headings:text-gray-900 dark:prose-headings:text-white prose-p:text-gray-600 dark:prose-p:text-slate-300">
            {!! $post->body !!}
        </div>

        <div class="mt-12 pt-8 border-t border-gray-100 dark:border-slate-700 text-center">
            <a href="{{ route('book') }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-teal-700 text-white rounded-xl font-semibold hover:bg-teal-700 transition-all shadow-sm hover:shadow-md active:scale-95">
                <x-icons name="calendar" class="w-5 h-5" />
                Book Your Stay
            </a>
        </div>
    </div>
</article>
@endsection
