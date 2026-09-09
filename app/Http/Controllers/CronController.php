<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token-protected endpoints triggered by external schedulers (e.g. Vercel Cron).
 */
class CronController extends Controller
{
    /**
     * Known placeholder values that must never be accepted as a real secret.
     * These can be left behind in a fresh .env.example or a rushed deploy.
     */
    private const PLACEHOLDER_SECRETS = [
        'change-me',
        'change_me',
        'changeme',
        'your-cron-secret',
        'your-secret',
        'secret',
    ];

    /**
     * Expire pending reservations past their hold window and release their
     * cottage date blocks. Guarded by the CRON_SECRET bearer token that Vercel
     * sends on every cron invocation.
     */
    public function releaseExpiredReservations(Request $request): Response
    {
        if (! $this->isAuthorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Explicit ?hours= input always wins (clamped); otherwise the command
        // falls back to the booking_hold_hours setting.
        $params = $request->filled('hours')
            ? ['--hours' => max(1, min(168, (int) $request->input('hours')))]
            : [];

        try {
            Artisan::call('reservations:release-expired', $params);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('Cron releaseExpiredReservations failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Validate the CRON_SECRET bearer token.
     *
     * Fails closed: rejects the request when the secret is missing, is still
     * a known placeholder, or does not match the token via a constant-time
     * comparison.
     */
    private function isAuthorized(Request $request): bool
    {
        $secret = (string) config('cron.secret');

        if ($secret === '') {
            Log::warning('Cron endpoint hit but CRON_SECRET is not configured.');

            return false;
        }

        if (in_array($secret, self::PLACEHOLDER_SECRETS, true)) {
            Log::warning('Cron endpoint hit but CRON_SECRET is still set to a placeholder value.');

            return false;
        }

        return hash_equals($secret, (string) $request->bearerToken());
    }
}
