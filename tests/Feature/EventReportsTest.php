<?php

use App\Models\EventReport;
use App\Models\User;

/*
 * Reports ("Report a change or cancellation", M09). A user's reports go with
 * their account. Follows were dropped (decisions.md, 25 Nov).
 */
it('deletes a user\'s reports when the account is deleted', function () {
    $user = User::factory()->create();
    EventReport::factory()->for($user)->create();

    $user->delete();

    expect(EventReport::query()->count())->toBe(0);
});
