import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Page-specific entry loaded only on the book/contact pages (via
// @stack('scripts')). Keeps the ~94 KB flatpickr bundle out of the shared
// public chunk. Exposed globally so the inline Alpine component on the
// booking page (bookingForm() -> initFlatpickr()) can call flatpickr(...).
window.flatpickr = flatpickr;
