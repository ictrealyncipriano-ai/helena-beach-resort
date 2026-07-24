<?php

use App\Http\Controllers\CottageController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response('ok', 200))->name('health');
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/cottages', [CottageController::class, 'index'])->name('cottages.index');
Route::get('/cottages/{cottage:slug}', [CottageController::class, 'show'])->name('cottages.show');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/contact', [InquiryController::class, 'create'])->name('contact');
Route::post('/contact', [InquiryController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('contact.store');
Route::get('/booking/confirmation/{inquiry}', [InquiryController::class, 'show'])->name('booking.confirmation');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/migrate-cloudflare', function (\Illuminate\Http\Request $request) {
        if (!in_array($request->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN])) {
            abort(403);
        }
        return view('admin.migrate-cloudflare');
    })->name('migrate-cloudflare');

    Route::post('/migrate-cloudflare', function (\Illuminate\Http\Request $request) {
        if (!in_array($request->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN])) {
            abort(403);
        }
        $exitCode = \Illuminate\Support\Facades\Artisan::call('cloudflare:migrate', ['from' => 'r2']);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return view('admin.migrate-cloudflare', compact('exitCode', 'output'));
    })->name('migrate-cloudflare.run');
});
