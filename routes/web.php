<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Owner area — managing venues and courts (owners only)
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::redirect('/', '/owner/venues')->name('home');
    Route::get('/venues', \App\Livewire\Owner\Venues\Index::class)->name('venues.index');
    Route::get('/venues/{venue}', \App\Livewire\Owner\Venues\Courts::class)->name('venues.courts');
});

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

require __DIR__.'/settings.php';
