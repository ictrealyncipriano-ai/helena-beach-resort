<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Cache;

/**
 * Handles all public pages (home, about, faq, services, reviews, sitemap).
 * Each method returns a Blade view with data fetched from the database.
 */
class PageController extends Controller
{
    public function home()
    {
        $cottages = Cottage::with('primaryPhoto')
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $gallery = Gallery::where('is_active', true)
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        $testimonials = Testimonial::active()->with('cottage')->take(3)->get();
        $avgRating = Testimonial::where('is_active', true)->avg('rating');

        return view('pages.home', compact('cottages', 'gallery', 'testimonials', 'avgRating'));
    }

    /** Static about page */
    public function about()
    {
        return view('pages.about');
    }

    /** Display FAQs sorted by sort_order */
    public function faq()
    {
        $faqs = Faq::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.faq', compact('faqs'));
    }

    /** Display resort services grouped by category */
    public function services()
    {
        $services = Service::active()->get()->groupBy('category');

        return view('pages.services', compact('services'));
    }

    /** Paginated guest reviews/testimonials */
    public function reviews()
    {
        $testimonials = Testimonial::active()->with('cottage')->paginate(12);

        return view('pages.reviews', compact('testimonials'));
    }

    /** Health check endpoint for Render / monitoring */
    public function health()
    {
        return response('ok', 200);
    }

    /** Generate XML sitemap for SEO, cached for 1 hour */
    public function sitemap()
    {
        $xml = Cache::remember('sitemap', 3600, function () {
            $cottages = Cottage::where('is_available', true)->get();
            $galleryLastmod = Gallery::where('is_active', true)->max('updated_at');

            $pages = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
                ['loc' => route('faq'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('book'), 'priority' => '0.8', 'changefreq' => 'weekly'],
                ['loc' => route('services'), 'priority' => '0.6', 'changefreq' => 'weekly'],
                ['loc' => route('reviews'), 'priority' => '0.6', 'changefreq' => 'weekly'],
                ['loc' => route('cottages.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('gallery.index'), 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $galleryLastmod],
                ['loc' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ];

            foreach ($cottages as $cottage) {
                $pages[] = [
                    'loc' => route('cottages.show', $cottage),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => $cottage->updated_at,
                ];
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($pages as $page) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . e($page['loc']) . '</loc>' . "\n";
                if (!empty($page['lastmod'])) {
                    $lastmod = $page['lastmod'];
                    $xml .= '    <lastmod>' . ($lastmod instanceof \Carbon\CarbonInterface ? $lastmod->toDateString() : date('Y-m-d', strtotime((string) $lastmod))) . '</lastmod>' . "\n";
                }
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
