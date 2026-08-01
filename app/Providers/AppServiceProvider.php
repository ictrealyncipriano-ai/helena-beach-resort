<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Vercel's function filesystem is read-only except /tmp. Route dompdf's
        // writeable paths there so invoice PDF generation keeps working.
        config([
            'dompdf.options.font_dir' => env('DOMPDF_FONT_DIR', storage_path('fonts')),
            'dompdf.options.font_cache' => env('DOMPDF_FONT_CACHE', storage_path('fonts')),
            'dompdf.options.temp_dir' => env('DOMPDF_TEMP_DIR', sys_get_temp_dir()),
        ]);
    }
}
