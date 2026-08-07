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
        // writeable paths there so invoice PDF generation keeps working. The
        // values come from config (not env) so they survive `config:cache`.
        config([
            'dompdf.options.font_dir' => config('app.dompdf.font_dir'),
            'dompdf.options.font_cache' => config('app.dompdf.font_cache'),
            'dompdf.options.temp_dir' => config('app.dompdf.temp_dir'),
        ]);
    }
}
