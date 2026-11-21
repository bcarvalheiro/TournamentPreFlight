<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SignInWithSocialAccount;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Sign in with Google, Discord or X (M06): one pair of routes for every
 * Socialite driver.
 */
class SocialAuthController extends Controller
{
    public function redirect(SocialProvider $provider): SymfonyRedirectResponse
    {
        return Socialite::driver($provider->value)->redirect();
    }

    public function callback(Request $request, SocialProvider $provider, SignInWithSocialAccount $signIn): RedirectResponse
    {
        // The provider sends ?error=access_denied when the player cancels.
        if ($request->has('error')) {
            return to_route('login')->withErrors(['email' => 'Sign-in was cancelled.']);
        }

        $user = $signIn->handle($provider, Socialite::driver($provider->value)->user());

        if ($user === null) {
            // Slice 6 replaces this with the welcome page for new identities.
            return to_route('login')->withErrors(['email' => 'No account uses this sign-in yet. Create an account with your email first.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
