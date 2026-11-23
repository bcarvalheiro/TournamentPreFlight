<?php

namespace App\Http\Controllers\Auth;

use App\Actions\CreateUserFromSocialIdentity;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The welcome page (M06): a new social identity, kept in the session as
 * `social_signup`, confirms its name and age before the account exists. The
 * email is the one its provider certified for.
 */
class WelcomeController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $signup = $this->pendingSignup($request);

        if ($signup === null) {
            return $this->backToLogin($request);
        }

        return Inertia::render('auth/welcome', [
            'name' => $signup['name'],
            'email' => $signup['email'],
            'provider' => SocialProvider::from($signup['provider'])->name,
        ]);
    }

    public function store(Request $request, CreateUserFromSocialIdentity $create): RedirectResponse
    {
        $signup = $this->pendingSignup($request);

        if ($signup === null) {
            return $this->backToLogin($request);
        }

        $user = $create->handle($signup, $request->only(['name', 'age_confirmed']));

        $request->session()->forget('social_signup');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * The sign-up the callback stored, or null when there is none or it expired.
     *
     * @return array{provider: string, provider_user_id: string, name: string|null, email: string, expires_at: int}|null
     */
    private function pendingSignup(Request $request): ?array
    {
        /** @var array{provider: string, provider_user_id: string, name: string|null, email: string, expires_at: int}|null $signup */
        $signup = $request->session()->get('social_signup');

        return $signup !== null && $signup['expires_at'] > now()->getTimestamp() ? $signup : null;
    }

    /**
     * Back to sign in: an expired sign-up is forgotten and says so.
     */
    private function backToLogin(Request $request): RedirectResponse
    {
        if (! $request->session()->has('social_signup')) {
            return to_route('login');
        }

        $request->session()->forget('social_signup');

        return to_route('login')->withErrors(['email' => 'That sign-in expired. Please try again.']);
    }
}
