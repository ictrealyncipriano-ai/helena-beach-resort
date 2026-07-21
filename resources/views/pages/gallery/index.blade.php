@extends('layouts.app')

@section('title', 'Gallery')
@section('description', 'Browse photos of Helena Beach Resort in Infanta, Quezon.')

@section('content')
{{-- Header --}}
<section class="pt-32 pb-16 bg-gradient-to-br from-teal-600 to-teal-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">Gallery</h1>
        <p class="text-teal-100 text-lg max-w-2xl mx-auto">Explore the beauty of Helena Beach Resort through photos.</p>
    </div>
</section>

{{-- Gallery Grid --}}
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($galleries->isEmpty())
        <div class="text-center py-20">
            <div class="text-6xl mb-4">📸</div>
            <h2 class="text-xl font-semibold text-gray-600">Gallery coming soon</h2>
            <p class="text-gray-400 mt-2">We're adding photos. Check back later!</p>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($galleries as $item)
            <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 group cursor-pointer" onclick="openModal(this)">
                <img src="{{ Storage::url($item->photo_path) }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
                @if($item->title)
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-end p-4">
                    <p class="text-white text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">{{ $item->title }}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- Lightbox Modal --}}
<div id="lightbox" class="fixed inset-0 z-50 bg-black/90 hidden items-center justify-center p-4" onclick="closeModal(event)">
    <button onclick="closeModal(event)" class="absolute top-4 right-4 text-white/70 hover:text-white text-3xl z-10">&times;</button>
    <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-lg">
</div>

<script>
let currentImages = [];
let currentIndex = 0;

function openModal(el) {
    const imgs = document.querySelectorAll('.aspect-square img');
    currentImages = Array.from(imgs).map(img => img.src);
    currentIndex = currentImages.indexOf(el.querySelector('img').src);
    showImage(currentIndex);
    document.getElementById('lightbox').classList.remove('hidden');
    document.getElementById('lightbox').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function showImage(index) {
    const img = document.getElementById('lightbox-img');
    img.src = currentImages[index];
}

function closeModal(e) {
    if (e.target === e.currentTarget || e.target.tagName === 'BUTTON') {
        document.getElementById('lightbox').classList.add('hidden');
        document.getElementById('lightbox').classList.remove('flex');
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('lightbox').classList.add('hidden');
        document.getElementById('lightbox').classList.remove('flex');
        document.body.style.overflow = '';
    }
});
</script>
@endsection
