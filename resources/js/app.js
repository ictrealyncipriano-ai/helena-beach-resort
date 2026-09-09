import './bootstrap';
import './form-validation';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { themeToggle } from './theme-toggle';

window.Alpine = Alpine;

Alpine.plugin(focus);

function cookieConsent() {
    // Consent cookie namespace is resort-neutral. The legacy `helena_consent`
    // cookie is still honored (dual-read) so returning visitors are never
    // re-prompted; all new writes use `resort_consent` (single-write).
    // TODO(Phase 2): drop the legacy read path below.
    const CONSENT_COOKIE = 'resort_consent';
    const LEGACY_CONSENT_COOKIE = 'helena_consent';
    function readConsent() {
        const match = document.cookie.match(new RegExp('(?:^|; )' + CONSENT_COOKIE + '=([^;]*)'))
            || document.cookie.match(new RegExp('(?:^|; )' + LEGACY_CONSENT_COOKIE + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
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
