<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security headers to every response: MIME-sniffing guard,
 * clickjacking frame denial, and a strict referrer policy. HSTS is only sent
 * over HTTPS in production so local http:// development is never affected.
 *
 * A cryptographically secure per-request CSP nonce is generated before the
 * request is handled and shared with all Blade views as $cspNonce, so inline
 * scripts can opt in with nonce="{{ $cspNonce ?? '' }}" without ever falling
 * back to 'unsafe-inline'. Views rendered off-request (queued mail, PDFs)
 * see an empty nonce via the null-coalescing default.
 *
 * script-src intentionally omits 'unsafe-eval': Alpine runs via the
 * @alpinejs/csp build (AST expression evaluator, no new Function), so all
 * x-* directives work under the strict policy. Consequences, enforced by
 * CspNonceTest: no inline on* handler attributes (nonces do not cover them;
 * bind listeners in nonce scripts instead) and no eval-dependent libraries.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com; "
            ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
            ."font-src 'self' https://fonts.bunny.net; img-src 'self' data: https:; "
            ."connect-src 'self' https://www.googletagmanager.com https://www.google-analytics.com; "
            ."object-src 'none'; base-uri 'self'; frame-ancestors 'none'"
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
