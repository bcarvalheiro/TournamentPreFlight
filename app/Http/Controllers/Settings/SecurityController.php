<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/security', [
            'passwordRules' => PasswordRule::defaults()->toPasswordRulesString(),
            'hasPassword' => $user->password !== null,
            'signInProviders' => $user->socialAccounts->map(fn (SocialAccount $account): string => $account->provider->name)->values()->all(),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }

    /**
     * Email a link to set a password, for an account without one (M06). The
     * inbox proves it is the owner; Fortify's reset page sets the password,
     * and it opens only signed out, so the player is signed out here.
     */
    public function sendLink(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->password !== null, 403);

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'Check your email to set your password.');
    }
}
