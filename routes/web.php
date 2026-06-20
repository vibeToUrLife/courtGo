<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Owner\BillingController;
use Illuminate\Support\Facades\Route;

// Public landing page. The hero search dropdown and sport tiles use the
// curated sport list so categories stay consistent for customers.
Route::get('/', function () {
    return view('welcome', [
        'sports' => collect(config('courtgo.sports')),
        'popularSports' => collect(config('courtgo.popular_sports')),
        'states' => collect(config('courtgo.states')),
    ]);
})->name('home');

// Public "for owners" marketing page (funnels into owner registration).
Route::view('/for-business', 'for-business')->name('for-business');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        // Customers have no dashboard — send them straight to the homepage.
        if (auth()->user()->role === \App\Enums\UserRole::Customer) {
            return redirect()->route('home');
        }

        return view('dashboard');
    })->name('dashboard');
});

// Customer: browse, book & pay, my bookings
Route::middleware('auth')->group(function () {
    Route::get('/courts', \App\Livewire\Browse::class)->name('courts.browse');
    Route::get('/venues/{venue}', \App\Livewire\VenueShow::class)->name('venues.show');
    Route::get('/courts/{court}', \App\Livewire\CourtShow::class)->name('courts.show');
    Route::get('/courts/{court}/sessions/{session}/book', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/cart/success', [BookingController::class, 'cartSuccess'])->name('bookings.cart.success');
    Route::get('/bookings/cart/cancel', [BookingController::class, 'cartCancel'])->name('bookings.cart.cancel');
    Route::get('/bookings/{booking}/pay', [BookingController::class, 'pay'])->name('bookings.pay');
    Route::get('/bookings/{booking}/success', [BookingController::class, 'success'])->name('bookings.success');
    Route::get('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/my-bookings', \App\Livewire\MyBookings::class)->name('bookings.mine');
    Route::get('/bookings/{booking}', \App\Livewire\BookingShow::class)->name('bookings.show');
});

// Owner area — managing venues and courts (owners only)
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::redirect('/', '/owner/venues')->name('home');
    Route::get('/venues', \App\Livewire\Owner\Venues\Index::class)->name('venues.index');
    Route::get('/venues/{venue}/photo', [\App\Http\Controllers\Owner\VenuePhotoController::class, 'edit'])->name('venues.photo.edit');
    Route::post('/venues/{venue}/photo', [\App\Http\Controllers\Owner\VenuePhotoController::class, 'update'])->name('venues.photo.update');
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
