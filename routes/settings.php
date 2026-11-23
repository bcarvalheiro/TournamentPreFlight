<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('settings')->group(function () {
    Route::redirect('/', '/settings/profile')->name('settings');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'edit')->name('profile.edit');
        Route::patch('profile', 'update')->name('profile.update');
        Route::delete('profile', 'destroy')->middleware('verified')->name('profile.destroy');
    });

    Route::controller(SecurityController::class)->middleware('verified')->group(function () {
        Route::get('security', 'edit')->middleware(RequirePassword::class)->name('security.edit');
        Route::put('password', 'update')->middleware('throttle:6,1')->name('user-password.update');
    });
});
