<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPortalController;
use App\Http\Controllers\CottageController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Monitoring Routes
|--------------------------------------------------------------------------
*/
Route::get('/health', [PageController::class, 'health'])->name('health');

/*
|--------------------------------------------------------------------------
| robots.txt is served dynamically by RobotsController; its Sitemap URL is
| derived from config('app.url') so it can never drift. (No static
| public/robots.txt fallback — deleted in Phase 1 so the dynamic route is
| authoritative.)
|--------------------------------------------------------------------------
*/
Route::get('/robots.txt', RobotsController::class);

/*
|--------------------------------------------------------------------------
| Static Pages
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/reviews', [PageController::class, 'reviews'])->name('reviews');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/booking-policy', [PageController::class, 'bookingPolicy'])->name('booking-policy');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Cottages
|--------------------------------------------------------------------------
*/
Route::get('/cottages', [CottageController::class, 'index'])->name('cottages.index');
Route::get('/cottages/{cottage:slug}', [CottageController::class, 'show'])->name('cottages.show');

/*
|--------------------------------------------------------------------------
| Gallery
|--------------------------------------------------------------------------
*/
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');

/*
|--------------------------------------------------------------------------
| News / Announcements
|--------------------------------------------------------------------------
*/
Route::get('/news', [PostController::class, 'index'])->name('news.index');
Route::get('/news/{post:slug}', [PostController::class, 'show'])->name('news.show');

/*
|--------------------------------------------------------------------------
| Availability (homepage widget, public read-only)
|--------------------------------------------------------------------------
*/
// GET only: the lookup never mutates state, so a plain GET is safe here.
// Throttled so a scraper cannot mine every cottage's full block calendar.
Route::get('/availability/check', [AvailabilityController::class, 'check'])
    ->middleware('throttle:availability')
    ->name('availability.check');

/*
|--------------------------------------------------------------------------
| Booking Flow
|--------------------------------------------------------------------------
*/
Route::get('/book', [BookingController::class, 'create'])->name('book');
Route::post('/book', [BookingController::class, 'store'])
    ->middleware('throttle:booking')
    ->name('book.store');

/*
|--------------------------------------------------------------------------
| Contact / Inquiry Flow
|--------------------------------------------------------------------------
*/
Route::get('/contact', [InquiryController::class, 'create'])->name('contact');
Route::post('/contact', [InquiryController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');
Route::get('/booking/confirmation/{inquiry}', [InquiryController::class, 'show'])
    ->middleware('throttle:lookup')
    ->name('booking.confirmation');

/*
|--------------------------------------------------------------------------
| Guest Booking Portal (lookup + self-cancel)
|--------------------------------------------------------------------------
*/
Route::get('/booking/lookup', [BookingPortalController::class, 'lookupForm'])->name('booking.portal.lookup');
Route::post('/booking/lookup', [BookingPortalController::class, 'lookup'])
    ->middleware('throttle:lookup')
    ->name('booking.portal.lookup.post');
Route::get('/booking/{inquiry}', [BookingPortalController::class, 'show'])->middleware('throttle:lookup')->name('booking.portal.show');
Route::get('/booking/{inquiry}/modify', [BookingPortalController::class, 'modifyForm'])
    ->middleware('throttle:lookup')
    ->name('booking.portal.modify');
Route::put('/booking/{inquiry}/modify', [BookingPortalController::class, 'modify'])
    ->middleware('throttle:modify')
    ->name('booking.portal.modify.update');
Route::get('/booking/{inquiry}/status', [BookingPortalController::class, 'status'])->middleware('throttle:lookup')->name('booking.portal.status');
Route::post('/booking/{inquiry}/cancel', [BookingPortalController::class, 'cancel'])
    ->middleware('throttle:cancel')
    ->name('booking.portal.cancel');
Route::post('/booking/{inquiry}/review', [BookingPortalController::class, 'review'])
    ->middleware('throttle:review')
    ->name('booking.portal.review');
Route::post('/booking/{inquiry}/payment-proof', [BookingPortalController::class, 'uploadPaymentProof'])
    ->middleware('throttle:review')
    ->name('booking.portal.proof');

/*
|--------------------------------------------------------------------------
| Invoice
|--------------------------------------------------------------------------
*/
Route::get('/booking/{inquiry}/invoice', [InvoiceController::class, 'show'])->middleware('throttle:invoice')->name('invoice.show');
Route::get('/booking/{inquiry}/invoice/pdf', [InvoiceController::class, 'download'])
    ->middleware('throttle:invoice')
    ->name('invoice.download');

/*
|--------------------------------------------------------------------------
| Payments (PayMongo hosted checkout)
|--------------------------------------------------------------------------
*/
// POST only: creating a checkout session is a side-effecting action and must
// not be triggered by a plain GET. Ownership is enforced in the controller.
Route::post('/booking/{inquiry}/pay', [PaymentController::class, 'pay'])
    ->middleware('throttle:payment')
    ->name('payment.pay');
Route::post('/paymongo/webhook', [PaymentController::class, 'webhook'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:webhook')
    ->name('payment.webhook');

/*
// Cron Endpoints (triggered by scheduler / Vercel Cron / external pinger)
//--------------------------------------------------------------------------
*/
// GET + POST: Vercel Cron invokes the path via GET, while manual/external
// triggers use POST. Releasing reservations mutates state, so both verbs
// require CRON_SECRET bearer auth (see CronController) plus throttle:cron.
// The scheduler in routes/console.php remains the primary trigger on hosts
// with a worker; this endpoint is the serverless trigger.
Route::match(['GET', 'POST'], '/cron/reservations', [CronController::class, 'releaseExpiredReservations'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:cron');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/admin.php';