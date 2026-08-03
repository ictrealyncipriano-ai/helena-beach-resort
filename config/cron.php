<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cron Job Secret
    |--------------------------------------------------------------------------
    |
    | Bearer token required to invoke cron-protected endpoints. Vercel Cron
    | automatically sends `Authorization: Bearer <CRON_SECRET>` on every
    | invocation when this env var is set on the project.
    |
    */
    'secret' => env('CRON_SECRET', ''),

];
