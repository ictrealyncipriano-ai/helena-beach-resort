<?php

use App\Http\Controllers\CottageController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Monitoring Routes
|--------------------------------------------------------------------------
*/
Route::get('/health', [PageController::class, 'health'])->name('health');
Route::get('/test-mail', [PageController::class, 'testMail']);

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
Route::get('/book', [App\Http\Controllers\BookingController::class, 'create'])->name('book');
Route::post('/book', [App\Http\Controllers\BookingController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('book.store');

/*
|--------------------------------------------------------------------------
| Contact / Inquiry Flow
|--------------------------------------------------------------------------
*/
Route::get('/contact', [InquiryController::class, 'create'])->name('contact');
Route::post('/contact', [InquiryController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('contact.store');
Route::get('/booking/confirmation/{inquiry}', [InquiryController::class, 'show'])->name('booking.confirmation');

/*
|--------------------------------------------------------------------------
| Guest Booking Portal (lookup + self-cancel)
|--------------------------------------------------------------------------
*/
Route::get('/booking/lookup', [App\Http\Controllers\BookingPortalController::class, 'lookupForm'])->name('booking.portal.lookup');
Route::post('/booking/lookup', [App\Http\Controllers\BookingPortalController::class, 'lookup'])->name('booking.portal.lookup.post');
Route::get('/booking/{inquiry}', [App\Http\Controllers\BookingPortalController::class, 'show'])->name('booking.portal.show');
Route::post('/booking/{inquiry}/cancel', [App\Http\Controllers\BookingPortalController::class, 'cancel'])
    ->middleware('throttle:3,1')
    ->name('booking.portal.cancel');

/*
|--------------------------------------------------------------------------
| Invoice
|--------------------------------------------------------------------------
*/
Route::get('/booking/{inquiry}/invoice', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoice.show');
Route::get('/booking/{inquiry}/invoice/pdf', [App\Http\Controllers\InvoiceController::class, 'download'])->name('invoice.download');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/admin.php';
