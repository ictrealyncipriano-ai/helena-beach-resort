<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\CottageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login']);
    });

    Route::middleware(['auth', AdminMiddleware::class])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', fn() => redirect()->route('admin.dashboard'))->name('home');

        Route::resource('cottages', CottageController::class);
        Route::resource('testimonials', TestimonialController::class);
        Route::resource('services', ServiceController::class);
        Route::resource('faqs', FaqController::class);
        Route::resource('gallery', GalleryController::class);
        Route::resource('users', UserController::class);
        Route::resource('site-settings', SiteSettingController::class)->parameters(['site-settings' => 'siteSetting']);
        Route::resource('guests', GuestController::class)->except(['create', 'store']);

        Route::prefix('inquiries')->name('inquiries.')->controller(InquiryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{inquiry}', 'show')->name('show');
            Route::get('{inquiry}/edit', 'edit')->name('edit');
            Route::put('{inquiry}', 'update')->name('update');
            Route::delete('{inquiry}', 'destroy')->name('destroy');
            Route::post('{inquiry}/confirm', 'confirm')->name('confirm');
            Route::post('{inquiry}/cancel', 'cancel')->name('cancel');
        });

        Route::post('faqs/activate-all', [FaqController::class, 'activateAll'])->name('faqs.activate-all');
    });
});
