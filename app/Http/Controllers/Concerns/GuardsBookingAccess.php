<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inquiry;
use Carbon\Carbon;

/**
 * Session-based ownership guard for the guest booking portal.
 *
 * After a successful email + reference lookup (or right after a new booking
 * is submitted) the inquiry's non-enumerable token is stored in the session.
 * Every portal route then requires the session to hold the token matching
 * that inquiry — closing the IDOR where any guessed {inquiry} id could be
 * viewed, cancelled, invoiced or paid.
 *
 * Two failure modes are handled differently:
 * - The session holds NO entry for the inquiry: abort 404. This stays
 *   indistinguishable from a request for an inquiry that does not exist,
 *   so guessed ids reveal nothing.
 * - The session DOES hold an entry but it no longer matches (expired
 *   window or rotated token): this browser previously had access, so we
 *   redirect the guest to the lookup page to re-authenticate instead of
 *   stranding them on a dead-end error page.
 */
trait GuardsBookingAccess
{
    /**
     * Grant the current session access to a booking, recording when the
     * grant happened so stale grants expire (see authorizeBookingAccess).
     */
    protected function grantBookingAccess(Inquiry $inquiry): void
    {
        $tokens = session('booking_access_tokens', []);

        if (! is_array($tokens)) {
            $tokens = [];
        }

        $tokens[$inquiry->id] = [
            'token' => $inquiry->token,
            'granted_at' => now()->toDateTimeString(),
        ];

        session(['booking_access_tokens' => $tokens]);
    }

    /**
     * Require the session to hold a fresh token matching the inquiry.
     * Grants older than 72 hours — and legacy grants without an age —
     * follow the expired path (re-lookup); otherwise 404 as before.
     */
    protected function authorizeBookingAccess(Inquiry $inquiry): void
    {
        $tokens = session('booking_access_tokens', []);
        $tokens = is_array($tokens) ? $tokens : [];

        $entry = $tokens[$inquiry->id] ?? null;

        if (is_array($entry)
            && isset($entry['token']) && is_string($entry['token'])
            && hash_equals((string) $inquiry->token, $entry['token'])
            && $this->grantIsFresh($entry['granted_at'] ?? null)
        ) {
            return;
        }

        if (array_key_exists($inquiry->id, $tokens)) {
            // Drop the stale entry so the retry starts from a clean state.
            unset($tokens[$inquiry->id]);
            session(['booking_access_tokens' => $tokens]);

            redirect()->route('booking.portal.lookup')
                ->with('error', 'Your booking session has expired. Please look up your booking again to continue.')
                ->throwResponse();
        }

        abort(404);
    }

    /**
     * A grant is fresh when it carries a parseable age inside the 72-hour
     * window. Missing or unparseable ages fail closed to the expired path.
     */
    private function grantIsFresh(mixed $grantedAt): bool
    {
        if (! is_string($grantedAt) || strtotime($grantedAt) === false) {
            return false;
        }

        return Carbon::parse($grantedAt)->greaterThan(now()->subHours(72));
    }
}
