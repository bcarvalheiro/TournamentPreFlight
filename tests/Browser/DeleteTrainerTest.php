<?php

use App\Models\PlayerProfile;
use App\Models\User;

/*
 * M07 AC1: "Delete" on a trainer card asks first (the Sheet: a dialog on
 * desktop), and only "Delete trainer" removes them. Opening a dialog and
 * confirming needs a real browser.
 */
it('asks before deleting a trainer, then removes only that one', function () {
    $parent = User::factory()->create();
    PlayerProfile::factory()->for($parent)->create(['name' => 'Ana Silva']);
    PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Rui Silva']);
    $this->actingAs($parent);

    $page = visit(route('profiles.index'));

    $page->click('[aria-label="Delete Rui Silva"]')
        ->assertSee('Delete Rui Silva?')
        ->click('Cancel')
        ->assertSee('Rui Silva')
        ->click('[aria-label="Delete Rui Silva"]')
        ->click('Delete trainer')
        ->assertDontSee('Rui Silva')
        ->assertSee('Ana Silva');

    expect($parent->playerProfiles()->pluck('name')->all())->toBe(['Ana Silva']);
});
