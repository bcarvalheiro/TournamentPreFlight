<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The player gate (M06): anyone may look at every page, but only a player
 * answers: a verified email and a player profile (its Pokemon Player ID (POP ID) is required);
 * a password is optional. The gate steps in only on the way to answering,
 * when the session holds a pending "Going?": a user who is not ready is
 * sent to verify their email, then to add their Pokemon Player ID (POP ID). Like the `auth`
 * middleware, it remembers the page it blocked as the intended URL
 * (`redirect()->guest()`), so verifying or saving the profile returns there.
 */
class EnsurePlayerReady
{
    /**
     * Routes a signed-in user can always reach: auth pages, settings and logout.
     *
     * @var list<string>
     */
    private const ALWAYS_OPEN = ['logout', 'events.going*', 'verification.*', 'password.*', 'settings', 'profile.*', 'security.*', 'user-password.*'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Anyone may look around; the gate only walks a user who pressed
        // "Going?" through what answering needs.
        if ($user === null || ! $request->session()->has('pending_intent') || $request->routeIs(self::ALWAYS_OPEN)) {
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
