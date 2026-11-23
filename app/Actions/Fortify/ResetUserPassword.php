<?php

namespace App\Actions\Fortify;

use App\Actions\RevokeUnprovenAccess;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private RevokeUnprovenAccess $revokeUnprovenAccess) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // The reset link reached the inbox, so this is the email's owner.
        if (! $user->hasVerifiedEmail()) {
            $this->revokeUnprovenAccess->handle($user);
        }

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
