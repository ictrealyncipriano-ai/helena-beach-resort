// Shared lightbox for the gallery grid + cottage photo viewer.
// Page-specific entry loaded only where a lightbox exists (via
// @vite('resources/js/lightbox.js')). Triggers are data attributes consumed
// by the delegated listener below — inline onclick handlers are blocked by
// script-src, so markup must never use them.
let currentImages = [];
let currentTitles = [];
let currentIndex = 0;
let lastTrigger = null;
let lastPhotoTrigger = null;

function openModal(el) {
    const items = document.querySelectorAll('#gallery-grid > button');
    currentImages = Array.from(items).map(item => item.dataset.src);
    currentTitles = Array.from(items).map(item => item.dataset.title);
    currentIndex = currentImages.indexOf(el.dataset.src);

    lastTrigger = el;
    showImage(currentIndex);
    const lightbox = document.getElementById('lightbox');
    if (! lightbox) return;
    lightbox.classList.remove('hidden');
    lightbox.classList.add('flex');
    document.body.style.overflow = 'hidden';
    lightbox.focus({ preventScroll: true });
}

function showImage(index) {
    const img = document.getElementById('lightbox-img');
    const caption = document.getElementById('lightbox-caption');
    if (! img) return;

    img.style.opacity = '0';
    setTimeout(() => {
        img.src = currentImages[index];
        img.alt = currentTitles[index] || '';
        img.style.opacity = '1';
    }, 150);

    if (caption) caption.textContent = currentTitles[index] || '';
    const counter = document.getElementById('lightbox-counter');
    if (counter) counter.textContent = (index + 1) + ' / ' + currentImages.length;
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

function closeModal() {
    document.getElementById('lightbox').classList.add('hidden');
    document.getElementById('lightbox').classList.remove('flex');
    document.body.style.overflow = '';
    if (lastTrigger) lastTrigger.focus({ preventScroll: true });
}

function openPhotoLightbox(el, src, alt) {
    lastPhotoTrigger = el;
    const img = document.getElementById('photo-lightbox-img');
    if (! img) return;
    img.src = src;
    img.alt = alt || '';
    const lb = document.getElementById('photo-lightbox');
    lb.classList.remove('hidden');
    lb.classList.add('flex');
    document.body.style.overflow = 'hidden';
    lb.focus({ preventScroll: true });
}

function closePhotoLightbox() {
    document.getElementById('photo-lightbox').classList.add('hidden');
    document.getElementById('photo-lightbox').classList.remove('flex');
    document.body.style.overflow = '';
    if (lastPhotoTrigger) lastPhotoTrigger.focus({ preventScroll: true });
}

// Delegated triggers (see header note). Overlay clicks close only when the
// click lands on the overlay itself or a control button — clicking the photo
// does nothing, matching the previous inline-handler behaviour.
document.addEventListener('click', function (e) {
    const opener = e.target.closest('[data-lightbox-open]');
    if (opener) {
        openModal(opener);
        return;
    }
    const photoOpener = e.target.closest('[data-photo-open]');
    if (photoOpener) {
        openPhotoLightbox(photoOpener, photoOpener.dataset.photoSrc || '', photoOpener.dataset.photoAlt || '');
        return;
    }
    const control = e.target.closest('[data-lightbox]');
    if (control) {
        const kind = control.dataset.lightbox;
        if (kind === 'prev') {
            prevImage(e);
            return;
        }
        if (kind === 'next') {
            nextImage(e);
            return;
        }
        if (e.target === control || e.target.closest('button')) closeModal();
        return;
    }
    const photoControl = e.target.closest('[data-photo-lightbox]');
    if (photoControl && (e.target === photoControl || e.target.closest('button'))) {
        closePhotoLightbox();
    }
});

document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('lightbox');
    if (lb && ! lb.classList.contains('hidden')) {
        if (e.key === 'Escape') {
            lb.classList.add('hidden');
            lb.classList.remove('flex');
            document.body.style.overflow = '';
            if (lastTrigger) lastTrigger.focus({ preventScroll: true });
        }
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
        return;
    }
    const plb = document.getElementById('photo-lightbox');
    if (plb && ! plb.classList.contains('hidden') && e.key === 'Escape') {
        plb.classList.add('hidden');
        plb.classList.remove('flex');
        document.body.style.overflow = '';
        if (lastPhotoTrigger) lastPhotoTrigger.focus({ preventScroll: true });
    }
});


