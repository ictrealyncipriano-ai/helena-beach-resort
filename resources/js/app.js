import './bootstrap';
import './form-validation';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { themeToggle } from './theme-toggle';

window.Alpine = Alpine;

Alpine.plugin(focus);

function cookieConsent() {
    return {
        show: false,
        init() {
            const match = document.cookie.match(/(?:^|; )helena_consent=([^;]*)/);
            if (!match) {
                this.show = true;
            }
        },
        setCookie(name, value, days) {
            const maxAge = days * 86400;
            document.cookie = name + '=' + encodeURIComponent(value) + ';path=/;max-age=' + maxAge + ';SameSite=Lax';
        },
        accept() {
            this.setCookie('helena_consent', 'granted', 365);
            if (window.gtag) {
                window.gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'analytics_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted'
                });
            }
            if (window.loadHelenaGtm) window.loadHelenaGtm();
            this.show = false;
        },
        decline() {
            this.setCookie('helena_consent', 'denied', 365);
            if (window.gtag) {
                window.gtag('consent', 'update', {
                    'ad_storage': 'denied',
                    'analytics_storage': 'denied',
                    'ad_user_data': 'denied',
                    'ad_personalization': 'denied'
                });
            }
            this.show = false;
        }
    };
}

// Expose to global scope so Alpine can resolve x-data="themeToggle()"
// (module-scoped functions are tree-shaken out of the production bundle).
window.themeToggle = themeToggle;
window.cookieConsent = cookieConsent;

Alpine.start();
