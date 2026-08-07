<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPortalController;
use App\Http\Controllers\CottageController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
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
| robots.txt (served dynamically so the Sitemap host can never drift from
| config('app.url')). A static public/robots.txt also exists as a fallback
| for hosts that serve static files before reaching Laravel.
|--------------------------------------------------------------------------
*/
Route::get('/robots.txt', function () {
    $base = rtrim(config('app.url'), '/');
    $content = "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /admin\n"
        . "\n"
        . "Sitemap: {$base}/sitemap.xml\n";

    return response($content, 200)->header('Content-Type', 'text/plain');
});

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
Route::get('/booking/confirmation/{inquiry}', [InquiryController::class, 'show'])->name('booking.confirmation');

/*
|--------------------------------------------------------------------------
| Guest Booking Portal (lookup + self-cancel)
|--------------------------------------------------------------------------
*/
Route::get('/booking/lookup', [BookingPortalController::class, 'lookupForm'])->name('booking.portal.lookup');
Route::post('/booking/lookup', [BookingPortalController::class, 'lookup'])
    ->middleware('throttle:lookup')
    ->name('booking.portal.lookup.post');
Route::get('/booking/{inquiry}', [BookingPortalController::class, 'show'])->name('booking.portal.show');
Route::get('/booking/{inquiry}/status', [BookingPortalController::class, 'status'])->name('booking.portal.status');
Route::post('/booking/{inquiry}/cancel', [BookingPortalController::class, 'cancel'])
    ->middleware('throttle:cancel')
    ->name('booking.portal.cancel');

/*
|--------------------------------------------------------------------------
| Invoice
|--------------------------------------------------------------------------
*/
Route::get('/booking/{inquiry}/invoice', [InvoiceController::class, 'show'])->name('invoice.show');
Route::get('/booking/{inquiry}/invoice/pdf', [InvoiceController::class, 'download'])->name('invoice.download');

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
    ->name('payment.webhook');

/*
|--------------------------------------------------------------------------
| Cron Endpoints (triggered by Vercel Cron)
|--------------------------------------------------------------------------
*/
// Vercel Cron fires GET requests; the bearer CRON_SECRET (hash_equals, fail-closed)
// is the only auth, so a GET that mutates state is safe here. POST is kept so the
// endpoints can still be triggered manually with curl.
Route::match(['get', 'post'], '/cron/reservations', [CronController::class, 'releaseExpiredReservations'])
    ->withoutMiddleware(VerifyCsrfToken::class);
Route::match(['get', 'post'], '/cron/migrate', [CronController::class, 'migrate'])
    ->withoutMiddleware(VerifyCsrfToken::class);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/admin.php';
