<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects non-canonical host/scheme to the canonical config('app.url').
 *
 * Only enforced in production (APP_ENV=production) so local development on
 * localhost is never affected. Handles www -> non-www and http -> https.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $canonical = parse_url((string) config('app.url'));

            $canonicalHost = $canonical['host'] ?? null;
            $canonicalScheme = $canonical['scheme'] ?? 'https';

            $currentHost = $request->getHost();
            $currentScheme = $request->getScheme();

            if ($canonicalHost && ($currentHost !== $canonicalHost || $currentScheme !== $canonicalScheme)) {
                $url = $canonicalScheme . '://' . $canonicalHost . $request->getRequestUri();

                return redirect($url, 301);
            }
        }

        return $next($request);
    }
}