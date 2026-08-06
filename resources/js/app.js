import './bootstrap';
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;
window.flatpickr = flatpickr;

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

// Expose to global scope so Alpine can resolve x-data="themeToggle()"
// (module-scoped functions are tree-shaken out of the production bundle).
window.themeToggle = themeToggle;

Alpine.start();
