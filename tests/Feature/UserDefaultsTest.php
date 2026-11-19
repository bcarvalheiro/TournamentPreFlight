<?php

use App\Models\User;

/*
 * Nobody is an admin by accident, and the one email setting (reminders) is on
 * until the user turns it off (product.md, Notifications). The new object knows
 * it straight away, not only after reading the row back.
 */
it('makes a new user a non-admin with reminders on', function () {
    $user = User::factory()->create();

    expect($user->notification_settings)->toBe(['reminders' => true]);

    $user->refresh();

    expect($user->is_admin)->toBeFalse()
        ->and($user->notification_settings)->toBe(['reminders' => true]);
});
