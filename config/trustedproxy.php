<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    | Comma-separated IPs/CIDRs from TRUSTED_PROXIES env, or "*" to trust all.
    | Read here (not in bootstrap/app.php) so config:cache stays valid.
    */
    'proxies' => env('TRUSTED_PROXIES', ''),
];
