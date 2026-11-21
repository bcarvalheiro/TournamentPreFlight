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

        $identity = Socialite::driver($provider->value)->user();
        $user = $signIn->handle($provider, $identity);

        // A new identity: it waits in the session until the welcome page's age box.
        if ($user === null) {
            $request->session()->put('social_signup', [
                'provider' => $provider->value,
                'provider_user_id' => $identity->getId(),
                'name' => $identity->getName() ?? $identity->getNickname(),
                'email' => $identity->getEmail(),
                'email_verified' => $provider->confirmsEmail($identity),
            ]);

            return to_route('welcome');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
