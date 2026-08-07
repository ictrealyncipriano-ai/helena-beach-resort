<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inquiry;

/**
 * Session-based ownership guard for the guest booking portal.
 *
 * After a successful email + reference lookup (or right after a new booking
 * is submitted) the inquiry's non-enumerable token is stored in the session.
 * Every portal route then aborts with 404 unless the session holds the token
 * matching that inquiry — closing the IDOR where any guessed {inquiry} id
 * could be viewed, cancelled, invoiced or paid. 404 (not 403) is used so the
 * existence of a booking is never revealed to an unauthenticated caller.
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
     * Abort 404 unless the session holds the token matching the inquiry.
     */
    protected function authorizeBookingAccess(Inquiry $inquiry): void
    {
        $tokens = session('booking_access_tokens', []);
        $expected = is_array($tokens) ? ($tokens[$inquiry->id] ?? null) : null;

        if (! is_string($expected) || ! hash_equals((string) $inquiry->token, $expected)) {
            abort(404);
        }
    }
}
