<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Temporary smoke route to verify role-based access (replaced by the real owner dashboard in Phase 2)
Route::get('/owner', fn () => 'Owner area')
    ->middleware(['auth', 'role:owner'])
    ->name('owner.dashboard');

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

require __DIR__.'/settings.php';
