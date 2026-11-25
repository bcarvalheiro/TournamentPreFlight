<?php

use App\Http\Middleware\EnsurePlayerReady;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->web(append: [
            EnsurePlayerReady::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Any rate limit on a save: back to the page with one message saying how
        // long to wait, instead of the bare 429 page (Inertia docs, CSRF
        // Protection, "Handling Mismatches"; decisions.md, 25 Nov). Page loads
        // keep the 429 page: going back could loop to the same URL.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($response->getStatusCode() !== Response::HTTP_TOO_MANY_REQUESTS || $request->isMethod('GET')) {
                return $response;
            }

            $seconds = (int) $response->headers->get('Retry-After');

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $seconds > 0
                    ? 'Too many tries. Wait '.$seconds.' '.Str::plural('second', $seconds).' and try again.'
                    : 'Too many tries. Wait a moment and try again.',
            ]);

            return back();
        });
    })->create();
