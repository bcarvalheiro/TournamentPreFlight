<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A guest pressed "I'm going" (M06): remember the event in the session, come
 * back to the same page after sign-in, and send them to sign in. M08 acts on
 * the intent once the page receives it.
 */
class PendingIntentController extends Controller
{
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        $request->session()->put('pending_intent', ['event_id' => $event->id]);
        redirect()->setIntendedUrl($this->returnTo($request));

        return redirect()->route('login');
    }

    /**
     * A full URL on this site: return_to is made absolute and kept only when its
     * host is ours; anything else (another site, "//host", nothing) goes home.
     */
    private function returnTo(Request $request): string
    {
        $returnTo = $request->query('return_to');

        if (! is_string($returnTo)) {
            return route('home');
        }

        $url = url($returnTo);

        return parse_url($url, PHP_URL_HOST) === $request->getHost() ? $url : route('home');
    }
}
