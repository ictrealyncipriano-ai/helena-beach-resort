<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compress text-based responses with gzip when the client supports it.
 *
 * Skips admin routes (small authenticated audience) and responses that are
 * already compressed, too small to benefit, or non-text content types.
 */
class CompressResponse
{
    /**
     * Content types eligible for gzip compression.
     */
    private const COMPRESSIBLE_TYPES = [
        'text/html',
        'text/css',
        'text/javascript',
        'text/xml',
        'text/plain',
        'application/json',
        'application/javascript',
        'application/xml',
        'application/rss+xml',
        'application/atom+xml',
        'image/svg+xml',
    ];

    /**
     * Minimum response size (bytes) before compression is applied.
     */
    private const MIN_SIZE = 1024;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Skip admin routes — authenticated dashboard traffic is low-volume
        // and the overhead of compression is not justified.
        if ($request->is('admin/*')) {
            return $response;
        }

        // Only compress when the client advertises gzip support.
        if (! str_contains((string) $request->header('Accept-Encoding', ''), 'gzip')) {
            return $response;
        }

        // Don't double-compress.
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        // Strip the charset suffix (e.g. "text/html; charset=UTF-8") for comparison.
        $baseType = strtolower(trim(explode(';', $contentType, 2)[0]));

        if (! in_array($baseType, self::COMPRESSIBLE_TYPES, true)) {
            return $response;
        }

        $body = $response->getContent();

        if (strlen($body) < self::MIN_SIZE) {
            return $response;
        }

        $compressed = gzencode($body, 6);

        // Only use the compressed version if it's actually smaller.
        if ($compressed === false || strlen($compressed) >= strlen($body)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
