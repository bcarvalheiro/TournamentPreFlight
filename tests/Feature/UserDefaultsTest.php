<?php

use App\Models\User;

/*
 * Nobody is an admin by accident, and the one email setting (reminders) is on
 * until the user turns it off (product.md, Notifications).
 */
it('makes a new user a non-admin with reminders on', function () {
    $user = User::factory()->create()->refresh();

    expect($user->is_admin)->toBeFalse()
        ->and($user->notification_settings)->toBe(['reminders' => true]);
});
