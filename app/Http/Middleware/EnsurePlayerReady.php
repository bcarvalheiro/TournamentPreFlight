<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The player gate (M06): a signed-in user uses the site only with a verified
 * email, a password and at least one player profile, so only players answer.
 * Guests are not affected. Like the `auth` middleware, it remembers the page it
 * blocked as the intended URL (`redirect()->guest()`), so verifying the email
 * or saving the first profile can return there with `redirect()->intended()`.
 */
class EnsurePlayerReady
{
    /**
     * Routes a signed-in user can always reach: auth pages, settings and logout.
     *
     * @var list<string>
     */
    private const ALWAYS_OPEN = ['logout', 'verification.*', 'password.*', 'settings', 'profile.*', 'security.*', 'user-password.*'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $request->routeIs(self::ALWAYS_OPEN)) {
            return $next($request);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->guest(route('verification.notice'));
        }

        // Every player-profile route stays open: the page and, from M07, saving it.
        if (! $user->isPlayerReady() && ! $request->routeIs('profiles.*')) {
            return redirect()->guest(route('profiles.create'));
        }

        return $next($request);
    }
}
