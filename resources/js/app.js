import './bootstrap';
import './form-validation';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { themeToggle } from './theme-toggle';

window.Alpine = Alpine;

Alpine.plugin(focus);

function cookieConsent() {
    // Consent state lives in the resort-neutral `resort_consent` cookie.
    // Malformed values are treated as absent (banner shown) rather than
    // throwing inside the Alpine component.
    const CONSENT_COOKIE = 'resort_consent';
    function readConsent() {
        const match = document.cookie.match(new RegExp('(?:^|; )' + CONSENT_COOKIE + '=([^;]*)'));
        if (!match) return null;
        try { return decodeURIComponent(match[1]); } catch (e) { return null; }
    }
    return {
        show: false,
        init() {
            if (!readConsent()) {
                this.show = true;
            }
        },
        setCookie(name, value, days) {
            const maxAge = days * 86400;
            document.cookie = name + '=' + encodeURIComponent(value) + ';path=/;max-age=' + maxAge + ';SameSite=Lax';
        },
        accept() {
            this.setCookie(CONSENT_COOKIE, 'granted', 365);
            if (window.gtag) {
                window.gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'analytics_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted'
                });
            }
            if (window.loadResortGtm) window.loadResortGtm();
            this.show = false;
        },
        decline() {
            this.setCookie(CONSENT_COOKIE, 'denied', 365);
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
