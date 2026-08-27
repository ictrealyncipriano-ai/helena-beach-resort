<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inquiry;

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
     * Grant the current session access to a booking.
     */
    protected function grantBookingAccess(Inquiry $inquiry): void
    {
        $tokens = session('booking_access_tokens', []);

        if (! is_array($tokens)) {
            $tokens = [];
        }

        $tokens[$inquiry->id] = $inquiry->token;

        session(['booking_access_tokens' => $tokens]);
    }

    /**
     * Require the session to hold the token matching the inquiry; otherwise
     * 404 (never had access) or redirect to the lookup page (access expired).
     */
    protected function authorizeBookingAccess(Inquiry $inquiry): void
    {
        $tokens = session('booking_access_tokens', []);
        $tokens = is_array($tokens) ? $tokens : [];

        $expected = $tokens[$inquiry->id] ?? null;

        if (is_string($expected) && hash_equals((string) $inquiry->token, $expected)) {
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
}
