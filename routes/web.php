<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TryOnController;
use App\Http\Middleware\EnsureAccountPagesEnabled;
use App\Http\Middleware\ExtractOpenAiKey;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Try-on needs no login. Ownership is tied to the session (TryOnController::owned).
Route::get('try-on', [TryOnController::class, 'create'])->name('try-on.create');

Route::prefix('try-on/requests')->name('try-on.requests.')->group(function () {
    Route::post('/', [TryOnController::class, 'store'])
        ->middleware([ExtractOpenAiKey::class, 'throttle:try-on'])
        ->name('store');

    Route::get('{id}', [TryOnController::class, 'show'])
        ->whereUuid('id')
        ->middleware('throttle:try-on-status')
        ->name('show');

    Route::delete('{id}', [TryOnController::class, 'destroy'])
        ->whereUuid('id')
        ->name('destroy');
});

// Starter-kit account area — 404 when account pages are off (EnsureAccountPagesEnabled).
Route::middleware([EnsureAccountPagesEnabled::class, 'auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard/DashboardPage')->name('dashboard');
});

Route::middleware(EnsureAccountPagesEnabled::class)->group(__DIR__.'/settings.php');
