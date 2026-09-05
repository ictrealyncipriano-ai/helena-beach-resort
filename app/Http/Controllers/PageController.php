<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Post;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Support\HtmlSanitizer;
use App\Support\PublicCache;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Handles all public pages (home, about, faq, services, reviews, sitemap).
 * Each method returns a Blade view with data fetched from the database.
 */
class PageController extends Controller
{
    public function home(): View
    {
        [$cottages, $gallery, $testimonials, $avgRating, $posts] = Cache::remember(PublicCache::HOME, PublicCache::HOME_TTL, function () {
            $cottages = Cottage::with('primaryPhoto')
                ->available()
                ->take(6)
                ->get();

            $gallery = Gallery::where('is_active', true)
                ->orderBy('sort_order')
                ->take(8)
                ->get();

            $testimonials = Testimonial::active()->with('cottage')->take(3)->get();
            $avgRating = Testimonial::where('is_active', true)->avg('rating');

            $posts = Post::active()->take(3)->get();

            return [$cottages, $gallery, $testimonials, $avgRating, $posts];
        });

        return view('pages.home', compact('cottages', 'gallery', 'testimonials', 'avgRating', 'posts'));
    }

    /** Static about page */
    public function about(): View
    {
        return view('pages.about');
    }

    public function faq(): View
    {
        $faqs = Cache::remember(PublicCache::FAQS_ALL, PublicCache::CONTENT_TTL, function () {
            return Faq::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return view('pages.faq', compact('faqs'));
    }

    /** Display resort services grouped by category */
    public function services(): View
    {
        $services = Cache::remember(PublicCache::SERVICES_ALL, PublicCache::CONTENT_TTL, function () {
            return Service::active()->get()->groupBy('category');
        });

        return view('pages.services', compact('services'));
    }

    /** Privacy Policy page (content editable via site settings) */
    public function privacy(HtmlSanitizer $sanitizer): View
    {
        return $this->legalPage('legal_privacy', 'Privacy Policy', $sanitizer);
    }

    /** Terms & Conditions page */
    public function terms(HtmlSanitizer $sanitizer): View
    {
        return $this->legalPage('legal_terms', 'Terms & Conditions', $sanitizer);
    }

    /** Booking / refund policy page */
    public function bookingPolicy(HtmlSanitizer $sanitizer): View
    {
        return $this->legalPage('legal_booking_policy', 'Booking Policy', $sanitizer);
    }

    /**
     * Render a legal page from the matching site-setting key. The stored
     * content is admin-entered HTML, so it is run through the same allow-list
     * sanitizer used for cottage descriptions before it is rendered.
     */
    private function legalPage(string $key, string $title, HtmlSanitizer $sanitizer)
    {
        $content = $sanitizer
            ->sanitize((string) SiteSetting::getValue($key, ''));

        return view('pages.legal', compact('title', 'content'));
    }

    /** Paginated guest reviews/testimonials */
    public function reviews(): View
    {
        $testimonials = $this->paginate(
            Cache::remember(PublicCache::REVIEWS_ALL, PublicCache::CONTENT_TTL, function () {
                return Testimonial::active()->with('cottage')->get();
            }),
            12
        );

        return view('pages.reviews', compact('testimonials'));
    }

    /** Health check endpoint for Render / monitoring */
    public function health(): Response
    {
        return response('ok', 200);
    }

    /** Generate XML sitemap for SEO, cached for 1 hour */
    public function sitemap(): Response
    {
        $xml = Cache::remember(PublicCache::SITEMAP, PublicCache::SITEMAP_TTL, function () {
            $cottages = Cottage::where('is_available', true)->get();
            $posts = Post::active()->get();
            $galleryLastmod = Gallery::where('is_active', true)->max('updated_at');

            $pages = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => date('Y-m-d')],
                ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('faq'), 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('book'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('services'), 'priority' => '0.6', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('reviews'), 'priority' => '0.6', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('news.index'), 'priority' => '0.6', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('cottages.index'), 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => date('Y-m-d')],
                ['loc' => route('gallery.index'), 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $galleryLastmod ? ($galleryLastmod instanceof CarbonInterface ? $galleryLastmod->toDateString() : date('Y-m-d', strtotime((string) $galleryLastmod))) : date('Y-m-d')],
                ['loc' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('terms'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => date('Y-m-d')],
                ['loc' => route('booking-policy'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => date('Y-m-d')],
            ];

            foreach ($cottages as $cottage) {
                $pages[] = [
                    'loc' => route('cottages.show', $cottage),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => $cottage->updated_at,
                ];
            }

            foreach ($posts as $post) {
                $pages[] = [
                    'loc' => route('news.show', $post),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                    'lastmod' => $post->updated_at,
                ];
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($pages as $page) {
                $xml .= '  <url>'."\n";
                $xml .= '    <loc>'.e($page['loc']).'</loc>'."\n";
                if (! empty($page['lastmod'])) {
                    $lastmod = $page['lastmod'];
                    $xml .= '    <lastmod>'.($lastmod instanceof CarbonInterface ? $lastmod->toDateString() : date('Y-m-d', strtotime((string) $lastmod))).'</lastmod>'."\n";
                }
                $xml .= '    <priority>'.$page['priority'].'</priority>'."\n";
                $xml .= '    <changefreq>'.$page['changefreq'].'</changefreq>'."\n";
                $xml .= '  </url>'."\n";
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
