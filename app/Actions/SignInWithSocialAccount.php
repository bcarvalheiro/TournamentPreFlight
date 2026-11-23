<?php

namespace App\Actions;

use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Finds the account a social identity signs in to, for every provider
 * (architecture.md, Auth flows): a known identity, or an account with the same
 * email when the provider says that email is verified, which is then linked.
 */
class SignInWithSocialAccount
{
    public function __construct(private RevokeUnprovenAccess $revokeUnprovenAccess) {}

    /**
     * The account to sign in, or null when the identity is new (sign-up).
     *
     * @throws ValidationException when the email has an account but the provider does not vouch for it
     */
    public function handle(SocialProvider $provider, SocialiteUser $identity): ?User
    {
        $known = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $identity->getId())
            ->first();

        if ($known !== null) {
            return $known->user;
        }

        $user = $identity->getEmail() === null
            ? null
            : User::query()->where('email', Str::lower($identity->getEmail()))->first();

        if ($user === null) {
            return null;
        }

        if (! $provider->confirmsEmail($identity)) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists. Sign in the way you did before.',
            ])->redirectTo(route('login'));
        }

        // The provider proved who owns this email: nothing set up by whoever
        // registered it without verifying keeps working, their password included.
        if (! $user->hasVerifiedEmail()) {
            $this->revokeUnprovenAccess->handle($user);
            $user->forceFill(['password' => null])->save();
        }

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_user_id' => $identity->getId(),
            'provider_email' => $identity->getEmail(),
        ]);

        return $user;
    }
}
