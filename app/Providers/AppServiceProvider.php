<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Vercel's function filesystem is read-only except /tmp. Route dompdf's
        // writeable paths there so invoice PDF generation keeps working. The
        // values come from config (not env) so they survive `config:cache`.
        config([
            'dompdf.options.font_dir' => config('app.dompdf.font_dir'),
            'dompdf.options.font_cache' => config('app.dompdf.font_cache'),
            'dompdf.options.temp_dir' => config('app.dompdf.temp_dir'),
        ]);

        foreach (['app.dompdf.font_dir', 'app.dompdf.font_cache'] as $key) {
            $dir = (string) config($key);

            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }

        $this->configureRateLimiters();
    }

    /**
     * Resolve the real client IP for rate limiting. Cloudflare (in front of
     * Vercel) injects CF-Connecting-IP and discards any client-supplied copy,
     * so it cannot be spoofed by an end user. Falling back to Request::ip()
     * keeps local/dev behaviour intact.
     */
    private function clientKey(Request $request): string
    {
        // Only trust Cloudflare's CF-Connecting-IP header in production, and
        // only when it is actually a valid IP. Anywhere else (and for any
        // malformed/spoofed value) fall back to the request's own IP so a
        // client-supplied header can never bypass a throttle.
        if (app()->environment('production')) {
            $cfIp = $request->header('CF-Connecting-IP');
            if (is_string($cfIp) && filter_var($cfIp, FILTER_VALIDATE_IP) !== false) {
                return $cfIp;
            }
        }

        return (string) $request->ip();
    }

    /**
     * Register the named rate limiters used by the throttled routes. Keyed on
     * the real client IP (see clientKey()) rather than the raw X-Forwarded-For
     * value so TRUSTED_PROXIES can stay broad for scheme detection without
     * letting spoofed headers bypass every throttle.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(3)->by($this->clientKey($request)));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(3)->by($this->clientKey($request)));
        RateLimiter::for('lookup', fn (Request $request) => Limit::perMinute(5)->by($this->clientKey($request)));
        RateLimiter::for('cancel', fn (Request $request) => Limit::perMinute(3)->by($this->clientKey($request)));
        RateLimiter::for('review', fn (Request $request) => Limit::perMinute(3)->by($this->clientKey($request)));
        RateLimiter::for('modify', fn (Request $request) => Limit::perMinute(3)->by($this->clientKey($request)));
        RateLimiter::for('payment', fn (Request $request) => Limit::perMinute(5)->by($this->clientKey($request)));
        // Public read-only date lookup used by the homepage availability
        // widget. Bounded to stop date scraping / quota abuse while leaving a
        // generous cap for normal browsing (each cottage/date selection fires
        // one request).
        RateLimiter::for('availability', fn (Request $request) => Limit::perMinute(60)->by($this->clientKey($request)));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by($this->clientKey($request)));
        // Signature-gated but not user-facing: a generous per-IP cap bounds
        // invalid-signature spam and PayMongo retry bursts without rejecting
        // legitimate webhook delivery.
        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(60)->by($this->clientKey($request)));
    }
}
