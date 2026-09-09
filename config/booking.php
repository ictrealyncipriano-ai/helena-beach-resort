<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking Reference Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for guest-facing booking reference codes (e.g. HB-XXXXXXXXXX).
    | Each resort deployment sets its own initials via BOOKING_REF_PREFIX
    | so a second resort never issues another resort's codes. Existing
    | codes keep working regardless of this value — it only affects
    | newly generated references.
    |
    */

    // Operational constraint: do not change BOOKING_REF_PREFIX while dated
    // inquiry / cottage-date-block records using the previous prefix exist;
    // the admin date-block protection recognizes the current configured
    // prefix only. Changing it mid-operation can expose live holds to edits.
    'reference_prefix' => env('BOOKING_REF_PREFIX', 'HB-'),

];
