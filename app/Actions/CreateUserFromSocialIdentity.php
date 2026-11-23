<?php

namespace App\Actions;

use App\Concerns\ProfileValidationRules;
use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Creates the account for a new social identity from the welcome page (M06),
 * validated like the kit's CreateNewUser. The email is the one the provider
 * certified for, so the account starts verified. It has no password until the
 * player sets one on the first-profile page.
 */
class CreateUserFromSocialIdentity
{
    use ProfileValidationRules;

    /**
     * @param  array{provider: string, provider_user_id: string, name: string|null, email: string}  $signup
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(array $signup, array $input): User
    {
        // The provider's email cannot be swapped for another one.
        $validated = Validator::make([...$input, 'email' => $signup['email']], [
            ...$this->profileRules(),
            'age_confirmed' => ['accepted'],
        ], [
            'age_confirmed.accepted' => 'Please confirm you are 13 or older.',
        ])->validate();

        return DB::transaction(function () use ($signup, $validated): User {
            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => null,
                'age_confirmed_at' => now(),
                'email_verified_at' => now(),
            ])->save();

            $user->socialAccounts()->create([
                'provider' => SocialProvider::from($signup['provider']),
                'provider_user_id' => $signup['provider_user_id'],
                'provider_email' => $signup['email'],
            ]);

            return $user;
        });
    }
}
