<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The owner of an unverified account's email has just proven it, by a
 * provider that vouches for it or by a password reset (M06 review). Whoever
 * registered that email with a password before may still be signed in: their
 * sessions and "remember me" token stop working, and the email counts as
 * verified from now on.
 */
class RevokeUnprovenAccess
{
    public function handle(User $user): void
    {
        // Sessions live in the database (SESSION_DRIVER=database, decisions.md).
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        $user->forceFill([
            'email_verified_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
    }
}
