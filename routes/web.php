<?php

use App\Http\Controllers\Auth\PendingIntentController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Placeholder until M07 builds the form; the player gate sends new players here.
Route::inertia('profiles/create', 'profiles/create')->middleware('auth')->name('profiles.create');

// A guest pressed "I'm going": remember it, then sign in (M06 pending intent).
Route::get('events/{event}/going', PendingIntentController::class)->middleware('guest')->name('events.going');

// Developer page with every shared component; it does not exist outside local.
if (app()->isLocal()) {
    Route::inertia('styleguide', 'styleguide')->name('styleguide');
}

require __DIR__.'/settings.php';
