<?php

use App\Enums\SocialProvider;
use App\Http\Controllers\Account\PlayerProfileController;
use App\Http\Controllers\Auth\PendingIntentController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\WelcomeController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Signed-in accounts
Route::middleware(['auth'])->group(function () {
    Route::middleware(['verified'])->group(function () {
        // The account's own player profiles (M07). A verified email comes first, so
        // nobody adds profiles to an email they have not proven.
        Route::controller(PlayerProfileController::class)
            ->prefix('profiles')
            ->name('profiles.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                // Only the trainer's own account (PlayerProfilePolicy); anyone else gets 403.
                Route::middleware('can:update,profile')->group(function () {
                    Route::get('{profile}/edit', 'edit')->name('edit');
                    Route::put('{profile}', 'update')->name('update');
                });
                Route::delete('{profile}', 'destroy')->can('delete', 'profile')->name('destroy');
            });
    });
});

Route::middleware('guest')->group(function () {
    // Sign in with Google, Discord or X: one pair of routes for every Socialite driver.
    Route::controller(SocialAuthController::class)
        ->prefix('auth/{provider}')
        ->whereIn('provider', SocialProvider::values())
        ->name('social.')
        ->group(function () {
            Route::get('redirect', 'redirect')->name('redirect');
            Route::get('callback', 'callback')->name('callback');
        });

    // A new social identity confirms its details and age before its account exists.
    Route::controller(WelcomeController::class)->group(function () {
        Route::get('welcome', 'show')->name('welcome');
        Route::post('welcome', 'store')->middleware('throttle:6,1')->name('welcome.store');
    });
});

// "I'm going" pressed (M06 pending intent): remember it, then sign in or go
// back to the page. POST only, so a link cannot set it.
Route::post('events/{event}/going', [PendingIntentController::class, 'store'])
    ->whereNumber('event')
    ->name('events.going');
// "Not now" on the profile page: forget the pending intent.
Route::delete('events/going', [PendingIntentController::class, 'destroy'])->name('events.going.forget');

// Developer page with every shared component; it does not exist outside local.
if (app()->isLocal()) {
    Route::inertia('styleguide', 'styleguide')->name('styleguide');
}

require __DIR__.'/settings.php';
