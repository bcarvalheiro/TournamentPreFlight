<?php

namespace App\Actions;

use App\Concerns\ProfileValidationRules;
use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Creates the account for a new social identity from the welcome page (M06),
 * validated like the kit's CreateNewUser. It has no password until the player
 * sets one on the first-profile page. Only an email the provider vouched for
 * counts as verified; any other gets the verification email.
 */
class CreateUserFromSocialIdentity
{
    use ProfileValidationRules;

    /**
     * @param  array{provider: string, provider_user_id: string, name: string|null, email: string|null, email_verified: bool}  $signup
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(array $signup, array $input): User
    {
        // The provider's email cannot be swapped for another one.
        $email = $signup['email_verified'] ? $signup['email'] : ($input['email'] ?? null);

        $validated = Validator::make([...$input, 'email' => $email], [
            ...$this->profileRules(),
            'age_confirmed' => ['accepted'],
        ], [
            'age_confirmed.accepted' => 'Please confirm you are 13 or older.',
        ])->validate();

        $user = DB::transaction(function () use ($signup, $validated): User {
            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => null,
                'age_confirmed_at' => now(),
                'email_verified_at' => $signup['email_verified'] ? now() : null,
            ])->save();

            $user->socialAccounts()->create([
                'provider' => SocialProvider::from($signup['provider']),
                'provider_user_id' => $signup['provider_user_id'],
                'provider_email' => $signup['email'],
            ]);

            return $user;
        });

        if (! $user->hasVerifiedEmail()) {
            event(new Registered($user));
        }

        return $user;
    }
}
