<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\Gallery;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    public function home()
    {
        try {
            $cottages = Cottage::where('is_available', true)
                ->orderBy('sort_order')
                ->take(6)
                ->get();

            $gallery = Gallery::where('is_active', true)
                ->orderBy('sort_order')
                ->take(8)
                ->get();
        } catch (QueryException) {
            $cottages = collect();
            $gallery = collect();
        }

        return view('pages.home', compact('cottages', 'gallery'));
    }

    public function about()
    {
        return view('pages.about');
    }

    public function sitemap()
    {
        $xml = Cache::remember('sitemap', 3600, function () {
            $cottages = Cottage::where('is_available', true)->get();

            $pages = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
                ['loc' => route('cottages.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('gallery.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['loc' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ];

            foreach ($cottages as $cottage) {
                $pages[] = [
                    'loc' => route('cottages.show', $cottage),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($pages as $page) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . e($page['loc']) . '</loc>' . "\n";
                $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
                $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
                $xml .= '  </url>' . "\n";
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
