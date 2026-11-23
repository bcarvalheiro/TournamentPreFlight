<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Middleware\ConfirmPasswordIfSet;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('settings')->group(function () {
    Route::redirect('/', '/settings/profile')->name('settings');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'edit')->name('profile.edit');
        Route::patch('profile', 'update')->name('profile.update');
        Route::delete('profile', 'destroy')->middleware('verified')->name('profile.destroy');
    });

    Route::controller(SecurityController::class)->middleware('verified')->group(function () {
        Route::get('security', 'edit')->middleware(ConfirmPasswordIfSet::class)->name('security.edit');
        Route::put('password', 'update')->middleware('throttle:6,1')->name('user-password.update');
        // A social account without a password asks for a link to set one (M06).
        Route::post('password/link', 'sendLink')->middleware('throttle:6,1')->name('user-password.link');
    });
});
