<?php

use App\Models\User;

/*
 * M07 AC3: the consent box appears as soon as a child birth year is typed
 * (Vitest renders static markup only, so this needs a real browser). Without
 * the tick the form explains why; with it the child's profile is saved with
 * the parent's consent.
 */
it('asks a parent for consent when the player is a child', function () {
    $parent = User::factory()->create();
    $this->actingAs($parent);
    $childYear = (string) (now('Europe/Lisbon')->year - 10);

    $page = visit(route('profiles.create'));

    // A parent who does not play adds only their child (decisions.md, 23 Nov).
    $page->assertSee('ADD A PLAYER')
        ->assertSee('You, or your child if they are under 13.')
        ->assertDontSee('Not now')
        // The server's English messages show, not the browser's own checks.
        ->fill('pop_id', '21das')
        ->click('Save player')
        ->assertSee('The Pokemon Player ID (POP ID) field must be between 1 and 10 digits.')
        ->assertSee('The birth year field is required.')
        ->fill('name', 'Young Trainer')
        ->fill('pop_id', '7654321')
        ->fill('birth_year', '1990')
        ->assertDontSee("I'm their parent or guardian")
        ->fill('birth_year', $childYear)
        ->assertSee("I'm their parent or guardian")
        ->click('Save player')
        ->assertSee('A parent must give consent for a player under 13.')
        ->click('#consent')
        ->click('Save player')
        ->assertPathIs('/');

    $profile = $parent->playerProfiles()->sole();
    expect($profile->is_child)->toBeTrue()
        ->and($profile->consent_by_user_id)->toBe($parent->id);
});
