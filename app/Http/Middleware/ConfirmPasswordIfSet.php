<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;

/**
 * Laravel's password confirmation (`RequirePassword`), skipped for an account
 * without a password (M06): a social account has none to confirm, and the
 * page it guards only offers to email a link to set one.
 */
class ConfirmPasswordIfSet extends RequirePassword
{
    /**
     * @param  Request  $request
     * @param  int|null  $passwordTimeoutSeconds
     */
    protected function shouldConfirmPassword($request, $passwordTimeoutSeconds = null): bool
    {
        return $request->user()?->password !== null
            && parent::shouldConfirmPassword($request, $passwordTimeoutSeconds);
    }
}
