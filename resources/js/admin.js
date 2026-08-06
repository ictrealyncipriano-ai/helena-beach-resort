import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';

window.Alpine = Alpine;
window.flatpickr = flatpickr;

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

Alpine.start();