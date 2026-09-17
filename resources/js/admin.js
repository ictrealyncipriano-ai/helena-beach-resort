// CSP build (see resources/js/app.js): no new Function, so the strict
// script-src without 'unsafe-eval' keeps working with Alpine.
import Alpine from '@alpinejs/csp';
import focus from '@alpinejs/focus';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import Chart from 'chart.js/auto';
import './form-validation';
import { themeToggle } from './theme-toggle';

window.Alpine = Alpine;
window.flatpickr = flatpickr;
// Self-hosted via Vite (replaces the former jsDelivr CDN tag) so the admin
// dashboard charts stay under script-src 'self'. Consumed by the vanilla
// dashboard-charts nonce script (charts cannot init from x-* attributes
// under the CSP expression parser).
window.Chart = Chart;

Alpine.plugin(focus);

Alpine.store('toasts', {
    items: [],
    add(message, type = 'success') {
        const id = Date.now();
        this.items.push({ id, message, type });
        setTimeout(() => {
            this.items = this.items.filter(i => i.id !== id);
        }, 4000);
    },
    remove(id) {
        this.items = this.items.filter(i => i.id !== id);
    }
});

Alpine.store('confirm', {
    open: false,
    title: 'Are you sure?',
    message: 'This action cannot be undone.',
    confirmText: 'Confirm',
    confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
    actionUrl: '',
    actionMethod: 'POST',
    show({ title, message, confirmText, confirmClass, url, method } = {}) {
        if (title) this.title = title;
        if (message) this.message = message;
        if (confirmText) this.confirmText = confirmText;
        if (confirmClass) this.confirmClass = confirmClass;
        this.actionUrl = url || '';
        this.actionMethod = method || 'POST';
        this.open = true;
    },
    close() {
        this.open = false;
    }
});

function liveSearchState() {
    return {
        search: '',
        init() {
            const form = this.$root;
            const input = form.querySelector('input[name="search"]');
            this.search = (input && input.value) || '';

            document.addEventListener('click', (e) => {
                const link = e.target.closest('[data-live-pagination] a[href]');
                if (!link) return;
                e.preventDefault();
                history.pushState({}, '', link.href);
                this.apply(link.href);
            });

            window.addEventListener('popstate', () => this.apply(window.location.href));
        },
        currentUrl() {
            const params = new URLSearchParams(new FormData(this.$root));
            params.forEach((value, key) => {
                if (value === '') params.delete(key);
            });
            params.delete('page');
            return window.location.pathname + '?' + params.toString();
        },
        apply(url) {
            const region = document.getElementById('admin-table-region');
            if (!region) {
                window.location.href = url;
                return;
            }
            region.classList.add('opacity-60', 'pointer-events-none');
            region.setAttribute('aria-busy', 'true');

            const bar = document.getElementById('admin-live-loading');
            if (bar) bar.classList.remove('hidden');
            const started = performance.now();

            fetch(url, {
                headers: { 'X-LiveSearch': '1' },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        window.location.href = url;
                        return null;
                    }
                    return response.text();
                })
                .then((html) => {
                    if (html === null) return;
                    region.innerHTML = html;
                    const total = region.querySelector('[data-total]');
                    if (total) {
                        document.querySelectorAll('[data-live-count]').forEach((el) => {
                            el.textContent = total.dataset.total;
                        });
                    }
                })
                .finally(() => {
                    const elapsed = performance.now() - started;
                    setTimeout(() => {
                        region.classList.remove('opacity-60', 'pointer-events-none');
                        region.removeAttribute('aria-busy');
                        if (bar) bar.classList.add('hidden');
                    }, Math.max(0, 250 - elapsed));
                });
        },
        goSearch() {
            const url = this.currentUrl();
            history.pushState({}, '', url);
            this.apply(url);
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // flatpickr ships in the shared vendor-flatpickr chunk (see vite.config
    // manualChunks); guard so admin pages without date inputs never throw.
    if (typeof flatpickr === 'undefined' && ! window.flatpickr) {
        return;
    }
    const fp = window.flatpickr || flatpickr;
    fp('.datepicker', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    fp('.datepicker-range', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        allowInput: true,
    });
});

Alpine.data('themeToggle', themeToggle);

// Generic modal + confirm dialog (x-admin.modal / x-admin.confirm-dialog).
// Methods live here in plain JS because the CSP expression parser supports
// no statements, globals, or `new` inside x-* attributes.
Alpine.data('resortModal', () => ({
    isOpen: false,
    title: '',
    data: {},
    _previousFocus: null,
    init() {
        this.title = this.$el.dataset.title || '';
    },
    open() {
        this._previousFocus = document.activeElement;
        this.isOpen = true;
    },
    close() {
        this.isOpen = false;
        this.data = {};
        window.dispatchEvent(new CustomEvent('resort:clear-validation'));
        if (this._previousFocus) {
            this._previousFocus.focus();
            this._previousFocus = null;
        }
    },
    handleOpen(e) {
        const detail = (e && e.detail) || {};
        this.open();
        this.title = detail.title || this.$el.dataset.title || '';
        this.data = detail.data || {};
        window.dispatchEvent(new CustomEvent('resort:clear-validation'));
    },
    handleEscape() {
        if (this.isOpen) this.close();
    },
}));

Alpine.data('resortConfirm', () => ({
    open: false,
    actionUrl: '',
    actionMethod: 'POST',
    _previousFocus: null,
    handleOpen(e) {
        const detail = (e && e.detail) || {};
        this._previousFocus = document.activeElement;
        this.open = true;
        this.actionUrl = detail.url || '';
        this.actionMethod = detail.method || 'POST';
    },
    handleEscape() {
        if (!this.open) return;
        this.open = false;
        if (this._previousFocus) {
            this._previousFocus.focus();
            this._previousFocus = null;
        }
    },
}));

// Dashboard stat cards: one self-observing counter per card (replaces the
// former parent-visibility + inline animate() x-data, which the CSP parser
// cannot evaluate). Each card animates when it scrolls into view —
// visually equivalent to the old shared-observer behaviour.
Alpine.data('statCounter', () => ({
    count: 0,
    target: 0,
    init() {
        this.target = parseInt(this.$el.dataset.target || '0', 10) || 0;
        const observer = new IntersectionObserver((entries) => {
            if (entries[0] && entries[0].isIntersecting) {
                this.animate();
                observer.disconnect();
            }
        }, { threshold: 0.1 });
        observer.observe(this.$el);
    },
    animate() {
        let current = 0;
        const step = Math.max(1, Math.floor(this.target / 30));
        const timer = setInterval(() => {
            current += step;
            if (current >= this.target) {
                current = this.target;
                clearInterval(timer);
            }
            this.count = current;
        }, 30);
    },
}));

// Page-level factories live in Blade nonce scripts (they need
// server-rendered data). Register whichever globals exist on this page so
// the CSP evaluator can resolve x-data="name()" references.
for (const name of ['liveSearchState', 'adminLayout', 'cottageModal', 'userForm', 'guestModal', 'galleryModal', 'siteSettingModal', 'faqModal', 'inquiryModal', 'testimonialModal', 'serviceModal', 'cottageForm']) {
    if (typeof window[name] === 'function') Alpine.data(name, window[name]);
}

Alpine.start();