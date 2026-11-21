<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
