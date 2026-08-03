<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token-protected endpoints triggered by external schedulers (e.g. Vercel Cron).
 */
class CronController extends Controller
{
    /**
     * Expire pending reservations past their hold window and release their
     * cottage date blocks. Guarded by the CRON_SECRET bearer token that Vercel
     * sends on every cron invocation.
     */
    public function releaseExpiredReservations(Request $request): Response
    {
        $secret = (string) config('cron.secret');

        if ($secret === '' || $request->bearerToken() !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            Artisan::call('reservations:release-expired --hours=48');

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run pending database migrations against the production database.
     * Guarded by the same CRON_SECRET bearer token so it can be triggered
     * manually (e.g. `curl -H "Authorization: Bearer <CRON_SECRET>" ...`)
     * and is never exposed to anonymous traffic.
     */
    public function migrate(Request $request): Response
    {
        $secret = (string) config('cron.secret');

        if ($secret === '' || $request->bearerToken() !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            Artisan::call('migrate --force', [], null);
            $migrateOutput = Artisan::output();

            Artisan::call('migrate:status', [], null);
            $statusOutput = Artisan::output();

            return response()->json([
                'ok' => true,
                'output' => $migrateOutput,
                'status' => $statusOutput,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * TEMPORARY: set a fixed demo total_amount on inquiry #2 (test data) so its
     * Pay Now flow can be validated end-to-end. Remove after verification.
     */
    public function setDemoAmount(Request $request): Response
    {
        $secret = (string) config('cron.secret');

        if ($secret === '' || $request->bearerToken() !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $result = \Illuminate\Support\Facades\DB::table('inquiries')
                ->where('id', (int) $request->query('id', 2))
                ->update(['total_amount' => 1500.00]);

            return response()->json(['ok' => true, 'affected' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
