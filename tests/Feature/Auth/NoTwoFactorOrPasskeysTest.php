<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
 * M06 AC4: sign-in is email and password or a social provider. Two-factor
 * auth, passkeys and magic links are gone: no routes, no columns, no tables.
 */
it('registers no two-factor or passkey routes', function () {
    expect(Route::has('two-factor.login'))->toBeFalse()
        ->and(Route::has('two-factor.enable'))->toBeFalse()
        ->and(Route::has('passkey.login'))->toBeFalse();

    $this->get('/two-factor-challenge')->assertNotFound();
    $this->get('/.well-known/passkey-endpoints')->assertNotFound();
});

it('keeps no two-factor columns, passkeys or magic login tokens', function () {
    expect(Schema::hasColumns('users', ['two_factor_secret']))->toBeFalse()
        ->and(Schema::hasColumns('users', ['two_factor_recovery_codes']))->toBeFalse()
        ->and(Schema::hasColumns('users', ['two_factor_confirmed_at']))->toBeFalse()
        ->and(Schema::hasTable('passkeys'))->toBeFalse()
        ->and(Schema::hasTable('magic_login_tokens'))->toBeFalse();
});
