<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Owner\BillingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Customer: browse, book & pay, my bookings
Route::middleware('auth')->group(function () {
    Route::get('/courts', \App\Livewire\Browse::class)->name('courts.browse');
    Route::get('/courts/{court}', \App\Livewire\CourtShow::class)->name('courts.show');
    Route::get('/courts/{court}/sessions/{session}/book', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/{booking}/success', [BookingController::class, 'success'])->name('bookings.success');
    Route::get('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/my-bookings', \App\Livewire\MyBookings::class)->name('bookings.mine');
});

// Owner area — managing venues and courts (owners only)
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::redirect('/', '/owner/venues')->name('home');
    Route::get('/venues', \App\Livewire\Owner\Venues\Index::class)->name('venues.index');
    Route::get('/venues/{venue}', \App\Livewire\Owner\Venues\Courts::class)->name('venues.courts');
    Route::get('/courts/{court}/schedule', \App\Livewire\Owner\Courts\Schedule::class)->name('courts.schedule');

    // Billing (subscription) + payouts (Stripe Connect)
    Route::get('/billing', \App\Livewire\Owner\Billing::class)->name('billing');
    Route::get('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::get('/billing/portal', [BillingController::class, 'billingPortal'])->name('billing.portal');
    Route::get('/connect', [BillingController::class, 'connect'])->name('connect.redirect');
    Route::get('/connect/return', [BillingController::class, 'connectReturn'])->name('connect.return');
    Route::get('/connect/refresh', [BillingController::class, 'connectRefresh'])->name('connect.refresh');
});

// Platform admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('home');
    Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/owners', \App\Livewire\Admin\Owners::class)->name('owners');
});

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Stripe Connect webhook (account.updated → owner onboarding status). Separate from Cashier's billing webhook.
Route::post('/stripe/connect/webhook', [\App\Http\Controllers\StripeConnectWebhookController::class, 'handle'])
    ->name('stripe.connect.webhook');

// Stripe Checkout webhook for BOOKING payments (confirms the booking once paid).
Route::post('/stripe/bookings/webhook', [\App\Http\Controllers\BookingWebhookController::class, 'handle'])
    ->name('stripe.bookings.webhook');

require __DIR__.'/settings.php';
