import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import './form-validation';

window.Alpine = Alpine;
window.flatpickr = flatpickr;

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

function themeToggle() {
    return {
        open: false,
        mode: localStorage.getItem('theme') || 'system',
        dark: false,
        init() {
            this.dark = this.isDarkMode();
            const mql = window.matchMedia('(prefers-color-scheme: dark)');
            if (typeof mql.addEventListener === 'function') {
                mql.addEventListener('change', (e) => {
                    if (this.mode === 'system') {
                        this.dark = e.matches;
                        document.documentElement.classList.toggle('dark', this.dark);
                    }
                });
            }
        },
        isDarkMode() {
            return this.mode === 'dark' || (this.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        },
        set(m) {
            this.mode = m;
            localStorage.setItem('theme', m);
            this.dark = this.isDarkMode();
            this.open = false;
            document.documentElement.classList.toggle('dark', this.dark);
        }
    };
}

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
    flatpickr('.datepicker', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    flatpickr('.datepicker-range', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        allowInput: true,
    });
});

// Expose to global scope so Alpine can resolve x-data="themeToggle()"
// (module-scoped functions are tree-shaken out of the production bundle).
window.themeToggle = themeToggle;
window.liveSearchState = liveSearchState;

Alpine.start();