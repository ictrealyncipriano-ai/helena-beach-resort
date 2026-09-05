<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves robots.txt dynamically so the Sitemap host can never drift from
 * config('app.url'). A static public/robots.txt also exists as a fallback
 * for hosts that serve static files before reaching Laravel.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $content = "User-agent: *\n"
            ."Allow: /\n"
            ."Disallow: /admin\n"
            ."\n"
            ."Sitemap: {$base}/sitemap.xml\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
