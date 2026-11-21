<?php

use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Developer page with every shared component; it does not exist outside local.
if (app()->isLocal()) {
    Route::inertia('styleguide', 'styleguide')->name('styleguide');
}

require __DIR__.'/settings.php';
