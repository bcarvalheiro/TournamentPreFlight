<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "I'm going" was pressed (M06): remember the event in the session. A guest
 * signs in first and comes back to the same page; a signed-in user goes
 * straight back to it. M08 acts on
 * the intent once the page receives it. Only events players can answer for
 * (listed, not ended) are accepted.
 */
class PendingIntentController extends Controller
{
    public function store(Request $request, int $event): RedirectResponse
    {
        $event = Event::query()->upcoming()->findOrFail($event);

        $request->session()->put('pending_intent', ['event_id' => $event->id]);
        $returnTo = $this->returnTo($request);

        // Signed in: back to the page, where the player gate walks a user who
        // is not ready through verifying and adding a Pokemon Player ID (POP ID), and a ready
        // player's page receives the intent.
        if ($request->user() !== null) {
            return redirect()->to($returnTo);
        }

        redirect()->setIntendedUrl($returnTo);

        return redirect()->route('login');
    }

    /**
     * "Not now" on the profile page: forget the intent and go back to the
     * page the player gate saved, so nobody is held on the profile page.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_intent');

        return redirect()->intended(route('home'));
    }

    /**
     * A full URL on this site: return_to is made absolute and kept only when its
     * host is ours; anything else (another site, "//host", nothing) goes home.
     */
    private function returnTo(Request $request): string
    {
        $returnTo = $request->input('return_to');

        if (! is_string($returnTo)) {
            return route('home');
        }

        $url = url($returnTo);

        return parse_url($url, PHP_URL_HOST) === $request->getHost() ? $url : route('home');
    }
}
