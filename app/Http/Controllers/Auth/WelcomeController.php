<?php

namespace App\Http\Controllers\Auth;

use App\Actions\CreateUserFromSocialIdentity;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The welcome page (M06): a new social identity, kept in the session as
 * `social_signup`, confirms its name, email and age before the account exists.
 */
class WelcomeController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        /** @var array{name: string|null, email: string|null, email_verified: bool}|null $signup */
        $signup = $request->session()->get('social_signup');

        if ($signup === null) {
            return to_route('login');
        }

        return Inertia::render('auth/welcome', [
            'name' => $signup['name'],
            'email' => $signup['email'],
            'emailLocked' => $signup['email_verified'],
        ]);
    }

    public function store(Request $request, CreateUserFromSocialIdentity $create): RedirectResponse
    {
        /** @var array{provider: string, provider_user_id: string, name: string|null, email: string|null, email_verified: bool}|null $signup */
        $signup = $request->session()->get('social_signup');

        if ($signup === null) {
            return to_route('login');
        }

        $user = $create->handle($signup, $request->only(['name', 'email', 'age_confirmed']));

        $request->session()->forget('social_signup');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
