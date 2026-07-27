<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        } catch (QueryException $e) {
            Log::error('Home page query failed: ' . $e->getMessage());
            $cottages = collect();
            $gallery = collect();
        }

            $testimonials = Testimonial::active()->take(3)->get();
            $avgRating = Testimonial::where('is_active', true)->avg('rating');
        } catch (QueryException $e) {
            Log::error('Home page testimonial query failed: ' . $e->getMessage());
            $testimonials = collect();
            $avgRating = null;
        }

        return view('pages.home', compact('cottages', 'gallery', 'testimonials', 'avgRating'));
    }

    public function about()
    {
        return view('pages.about');
    }

    public function faq()
    {
        try {
            $faqs = Faq::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        } catch (QueryException $e) {
            Log::error('FAQ query failed: ' . $e->getMessage());
            $faqs = collect();
        }

        return view('pages.faq', compact('faqs'));
    }

    public function services()
    {
        $services = Service::active()->get()->groupBy('category');
        return view('pages.services', compact('services'));
    }

    public function reviews()
    {
        $testimonials = Testimonial::active()->paginate(12);
        return view('pages.reviews', compact('testimonials'));
    }

    public function health()
    {
        return response('ok', 200);
    }

    public function sitemap()
    {
        $xml = Cache::remember('sitemap', 3600, function () {
            $cottages = Cottage::where('is_available', true)->get();

            $pages = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
                ['loc' => route('faq'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('services'), 'priority' => '0.6', 'changefreq' => 'weekly'],
                ['loc' => route('reviews'), 'priority' => '0.6', 'changefreq' => 'weekly'],
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
