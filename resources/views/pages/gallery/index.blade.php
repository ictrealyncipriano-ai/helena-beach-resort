@extends('layouts.app')

@section('title', 'Gallery')
@section('description', 'Browse photos of Helena Beach Resort in Infanta, Quezon.')
@section('canonical', route('gallery.index'))

@section('content')
<x-hero title="Gallery" subtitle="Explore the beauty of Helena Beach Resort through photos." />

{{-- Gallery Grid --}}
<section class="py-20 sm:py-28 bg-white dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($galleries->isEmpty())
        <div class="text-center py-20">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-6 text-gray-500 dark:text-slate-400">
                <x-icons name="photo" class="w-8 h-8" />
            </div>
            <h2 class="text-xl font-semibold text-gray-600 dark:text-slate-300">Gallery coming soon</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-2">We're adding photos. Check back later!</p>
        </div>
        @else
        <div id="gallery-grid" class="columns-2 sm:columns-3 lg:columns-4 gap-4 space-y-4">
            @foreach($galleries as $i => $item)
            <button type="button"
                 class="block w-full break-inside-avoid rounded-xl overflow-hidden bg-gray-100 dark:bg-slate-800 group cursor-pointer relative text-left reveal {{ $i > 0 ? 'reveal-delay-' . min($i % 4 + 1, 4) : '' }}"
                 onclick="openModal(this)"
                 data-src="{{ Storage::url($item->photo_path) }}"
                 data-title="{{ $item->title ?? '' }}"
                 aria-haspopup="dialog">
                {{-- width/height dimensions for the masonry layout are not
                     stored in the DB yet (requires the resize pipeline that
                     records intrinsic sizes — see Phase 3.7 follow-up). The
                     column layout preserves natural heights; these attributes
                     keep the browser from decoding offscreen images eagerly. --}}
                <img src="{{ Storage::url($item->photo_path) }}" alt="{{ $item->title ?: 'Helena Beach Resort — gallery photo' }}"
                     class="w-full h-auto object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy" decoding="async">
                <span class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-300 flex flex-col items-center justify-center">
                    @if($item->title)
                    <span class="text-white text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-300 px-4 text-center">{{ $item->title }}</span>
                    @endif
                    <span class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <x-icons name="search" class="w-6 h-6 text-white" />
                    </span>
                </span>
            </button>
            @endforeach
        </div>
        <div class="mt-12 reveal">
            {{ $galleries->links() }}
        </div>
        @endif
    </div>
</section>

{{-- Lightbox Modal --}}
<div id="lightbox" role="dialog" aria-modal="true" aria-label="Photo gallery" tabindex="-1" class="fixed inset-0 z-50 bg-black/95 hidden items-center justify-center p-4" onclick="closeModal(event)">
    <button onclick="closeModal(event)" class="absolute top-4 right-4 w-12 h-12 flex items-center justify-center text-white/60 hover:text-white rounded-full hover:bg-white/10 transition-all z-30" aria-label="Close">
        <x-icons name="x" class="w-6 h-6" />
    </button>

    <button onclick="prevImage(event)" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center text-white/60 hover:text-white rounded-full hover:bg-white/10 transition-all z-30" aria-label="Previous">
        <x-icons name="chevron-left" class="w-8 h-8" />
    </button>

    <button onclick="nextImage(event)" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center text-white/60 hover:text-white rounded-full hover:bg-white/10 transition-all z-30" aria-label="Next">
        <x-icons name="chevron-right" class="w-8 h-8" />
    </button>

    <div class="relative flex flex-col items-center gap-6 max-w-full max-h-full">
        <img id="lightbox-img" src="" alt=""
             class="max-w-full max-h-[80vh] object-contain rounded-xl transition-all duration-300 shadow-2xl">
        <div class="text-center">
            <p id="lightbox-caption" class="text-white/80 text-sm text-center px-4"></p>
            <p id="lightbox-counter" class="text-white/40 text-xs text-center mt-1"></p>
        </div>
    </div>
</div>

<script>
let currentImages = [];
let currentTitles = [];
let currentIndex = 0;
let lastTrigger = null;

function openModal(el) {
    const items = document.querySelectorAll('#gallery-grid > button');
    currentImages = Array.from(items).map(item => item.dataset.src);
    currentTitles = Array.from(items).map(item => item.dataset.title);
    currentIndex = currentImages.indexOf(el.dataset.src);

    lastTrigger = el;
    showImage(currentIndex);
    const lightbox = document.getElementById('lightbox');
    lightbox.classList.remove('hidden');
    lightbox.classList.add('flex');
    document.body.style.overflow = 'hidden';
    lightbox.focus({ preventScroll: true });
}

function showImage(index) {
    const img = document.getElementById('lightbox-img');
    const caption = document.getElementById('lightbox-caption');

    img.style.opacity = '0';
    setTimeout(() => {
        img.src = currentImages[index];
        img.alt = currentTitles[index] || '';
        img.style.opacity = '1';
    }, 150);

    caption.textContent = currentTitles[index] || '';
    document.getElementById('lightbox-counter').textContent = (index + 1) + ' / ' + currentImages.length;
    currentIndex = index;
}

function nextImage(e) {
    if (e) { e.stopPropagation(); }
    const next = (currentIndex + 1) % currentImages.length;
    showImage(next);
}

function prevImage(e) {
    if (e) { e.stopPropagation(); }
    const prev = (currentIndex - 1 + currentImages.length) % currentImages.length;
    showImage(prev);
}

function closeModal(e) {
    if (e.target === e.currentTarget || e.target.closest('button')) {
        document.getElementById('lightbox').classList.add('hidden');
        document.getElementById('lightbox').classList.remove('flex');
        document.body.style.overflow = '';
        if (lastTrigger) lastTrigger.focus({ preventScroll: true });
    }
}

document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('lightbox');
    if (lb.classList.contains('hidden')) return;

    if (e.key === 'Escape') {
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
        if (lastTrigger) lastTrigger.focus({ preventScroll: true });
    }
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
});
</script>
@endsection
