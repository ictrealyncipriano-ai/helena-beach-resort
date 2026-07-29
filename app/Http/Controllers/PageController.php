<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles all public pages (home, about, faq, services, reviews, sitemap).
 * Each method returns a Blade view with data fetched from the database.
 */
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

        try {
            $testimonials = Testimonial::active()->take(3)->get();
            $avgRating = Testimonial::where('is_active', true)->avg('rating');
        } catch (QueryException $e) {
            Log::error('Home page testimonial query failed: ' . $e->getMessage());
            $testimonials = collect();
            $avgRating = null;
        }

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

    /** Display resort services grouped by category */
    public function services()
    {
        try {
            $services = Service::active()->get()->groupBy('category');
        } catch (QueryException $e) {
            Log::error('Services page query failed: ' . $e->getMessage());
            $services = collect();
        }

        return view('pages.services', compact('services'));
    }

    /** Paginated guest reviews/testimonials */
    public function reviews()
    {
        try {
            $testimonials = Testimonial::active()->paginate(12);
        } catch (QueryException $e) {
            Log::error('Reviews page query failed: ' . $e->getMessage());
            $testimonials = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);
        }

        return view('pages.reviews', compact('testimonials'));
    }

    /** Health check endpoint for Render / monitoring */
    public function health()
    {
        return response('ok', 200);
    }

    /** Debug endpoint to test email delivery via the configured mailer */
    public function testMail()
    {
        $ownerEmail = SiteSetting::getValue('contact_email', 'ict.realyncipriano@gmail.com');
        try {
            Mail::raw('Test email from Helena Beach Resort at ' . now(), function ($msg) use ($ownerEmail) {
                $msg->to($ownerEmail)->subject('SMTP Test – ' . now()->format('Y-m-d H:i:s'));
            });
            return response('Mail sent successfully to ' . $ownerEmail, 200);
        } catch (\Exception $e) {
            return response('Mail failed: ' . $e->getMessage(), 500);
        }
    }

    /** Generate XML sitemap for SEO, cached for 1 hour */
    public function sitemap()
    {
        $xml = Cache::remember('sitemap', 3600, function () {
            $cottages = Cottage::where('is_available', true)->get();

            $pages = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
                ['loc' => route('faq'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('book'), 'priority' => '0.8', 'changefreq' => 'weekly'],
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
