<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only trust proxies listed in TRUSTED_PROXIES (comma-separated
        // IPs/CIDRs). Empty by default = trust none, so spoofed
        // X-Forwarded-* headers are ignored unless explicitly configured.
        // The literal value "*" must be passed as the string "*" (not the
        // array ["*"]) for Symfony to interpret it as "trust all proxies".
        $trustedProxiesRaw = trim((string) (env('TRUSTED_PROXIES') ?: ''));

        if ($trustedProxiesRaw === '*') {
            $trustedProxies = '*';
        } elseif ($trustedProxiesRaw !== '') {
            $trustedProxies = collect(explode(',', $trustedProxiesRaw))
                ->map(fn (string $ip) => trim($ip))
                ->filter()
                ->values()
                ->all();
        } else {
            $trustedProxies = [];
        }

        $middleware->trustProxies(at: $trustedProxies);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // 301-redirect non-canonical host/scheme to config('app.url') in
        // production (www -> non-www, http -> https). No-op in local dev.
        // Must run AFTER TrustProxies (which sits earlier in the global stack)
        // so getScheme() reflects X-Forwarded-Proto behind Vercel/Cloudflare.
        $middleware->append(\App\Http\Middleware\CanonicalHost::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\CompressResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // When the admin-login or password-reset throttle fires, redirect
        // back to the form with a user-friendly error instead of a raw 429.
        $exceptions->renderable(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->is('admin/login') || $request->is('admin/password/*')) {
                return back()->withErrors([
                    'email' => 'Too many failed attempts. Please try again in a few minutes.',
                ])->onlyInput('email');
            }
        });
    })->create();
