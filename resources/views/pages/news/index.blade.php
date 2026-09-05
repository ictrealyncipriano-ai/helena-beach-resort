@extends('layouts.app')

@section('title', 'News & Updates')
@section('description', 'News, announcements, and promotions from Helena Beach Resort in Infanta, Quezon.')
@section('canonical', $posts->currentPage() > 1 ? route('news.index', ['page' => $posts->currentPage()]) : route('news.index'))

@section('content')
<x-hero title="News &amp; Updates"
         subtitle="Announcements, tips, and promos from Helena Beach Resort.">
    <x-slot:badge>
        <x-icons name="sparkles" class="w-4 h-4" />
        Stay in the loop
    </x-slot:badge>
</x-hero>

<section class="py-20 sm:py-28 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($posts->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-6 text-gray-500 dark:text-slate-400">
                    <x-icons name="sparkles" class="w-8 h-8" />
                </div>
                <h2 class="text-xl font-semibold text-gray-600 dark:text-slate-300">No posts yet</h2>
                <p class="text-gray-500 dark:text-slate-400 mt-2">Check back soon for news and announcements.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-teal-700 text-white rounded-xl font-medium hover:bg-teal-800 transition-colors shadow-sm">
                    <x-icons name="email" class="w-5 h-5" />
                    Contact Us
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($posts as $i => $post)
                <a href="{{ route('news.show', $post) }}"
                   class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden hover:shadow-lg hover:border-teal-100 dark:hover:border-teal-800 transition-all duration-300 reveal {{ $i > 0 ? 'reveal-delay-' . min($i % 3 + 1, 4) : '' }}">
                    <div class="aspect-[16/9] bg-gray-100 dark:bg-slate-700 overflow-hidden">
                        @if($post->cover_image)
                            <img src="{{ Storage::url($post->cover_image) }}" alt="{{ $post->title }}" loading="lazy" width="600" height="338" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-teal-600 dark:text-teal-400">
                                <x-icons name="sparkles" class="w-10 h-10" />
                            </div>
                        @endif
                    </div>
                    <div class="p-6">
                        <p class="text-xs font-medium text-teal-700 dark:text-teal-300 uppercase tracking-wider mb-2">
                            {{ $post->published_at->format('F j, Y') }}
                        </p>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-teal-700 dark:group-hover:text-teal-300 transition-colors mb-2">
                            {{ $post->title }}
                        </h2>
                        @if($post->excerpt)
                            <p class="text-sm text-gray-600 dark:text-slate-300 leading-relaxed line-clamp-3">{{ $post->excerpt }}</p>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
            <div class="mt-12 reveal">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
