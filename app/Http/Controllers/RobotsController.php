<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves robots.txt dynamically; its Sitemap URL is derived from
 * config('app.url') so it can never drift. (No static public/robots.txt
 * fallback — deleted in Phase 1 so this route is authoritative.)
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
