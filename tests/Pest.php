<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Re-register the web routes as if the app had booted in another environment.
 *
 * Tests always boot in "testing". Routes that exist only in some environments
 * (such as the local-only /styleguide) are decided when routes/web.php runs, so
 * the test switches the environment and runs the routes file again.
 */
function registerWebRoutesIn(string $environment): void
{
    app()->detectEnvironment(fn (): string => $environment);

    Route::middleware('web')->group(base_path('routes/web.php'));

    app('router')->getRoutes()->refreshNameLookups();
}
