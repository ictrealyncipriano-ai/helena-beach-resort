import './bootstrap';
import './form-validation';
// CSP build: evaluates x-* attribute expressions with an AST interpreter
// instead of new Function, so the strict script-src (no 'unsafe-eval') stays
// intact. Component factories below are registered via Alpine.data() because
// the CSP evaluator resolves names from registrations, never from window.
import Alpine from '@alpinejs/csp';
import focus from '@alpinejs/focus';
import { themeToggle } from './theme-toggle';

window.Alpine = Alpine;

Alpine.plugin(focus);

Alpine.data('themeToggle', themeToggle);

Alpine.data('navbar', () => ({
    scrolled: false,
    solid: false,
    init() {
        // `solid` is rendered server-side for light-top pages (see navbar
        // blade data-solid); `window` access lives here in plain JS because
        // the CSP expression parser allows no globals in x-* attributes.
        this.solid = this.$el.dataset.solid === '1';
        this.update();
        window.addEventListener('scroll', () => this.update(), { passive: true });
    },
    update() {
        this.scrolled = window.scrollY > 20;
    },
}));

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

// Page-level factories live in Blade nonce scripts (they need
// server-rendered data). Register whichever globals exist on this page so
// the CSP evaluator can resolve x-data="name()" references.
for (const name of ['bookingForm', 'modifyForm', 'testimonialCarousel', 'availabilityWidget', 'cottageFilter', 'calendar', 'bookingCancel']) {
    if (typeof window[name] === 'function') Alpine.data(name, window[name]);
}

// Registered directly (module scope): the CSP evaluator resolves the
// x-data="cookieConsent()" reference from Alpine.data registrations.
Alpine.data('cookieConsent', cookieConsent);

Alpine.start();
