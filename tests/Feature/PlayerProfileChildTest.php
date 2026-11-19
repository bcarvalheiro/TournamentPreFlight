<?php

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/*
 * AC5 and CLAUDE.md rule 2: a profile under 13 this calendar year is a child
 * profile, and it cannot be saved without the parent's recorded consent.
 * Time is frozen in 2026.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-06-15 12:00', 'UTC'));
});

it('sets is_child from the birth year', function (int $birthYear, bool $isChild) {
    $parent = User::factory()->create();

    $profile = PlayerProfile::factory()->for($parent)->create([
        'birth_year' => $birthYear,
        'consent_given_at' => now(),
        'consent_by_user_id' => $parent->id,
    ]);

    expect($profile->is_child)->toBe($isChild);
})->with([
    'born 2014, turns 12 this year' => [2014, true],
    'born 2013, turns 13 this year' => [2013, false],
]);

it('refuses a child profile without recorded consent', function (array $consent) {
    $parent = User::factory()->create();

    $save = fn () => PlayerProfile::factory()->for($parent)->create(['birth_year' => 2016, ...$consent]);

    expect($save)->toThrow(function (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('consent');
    })
        ->and(PlayerProfile::query()->count())->toBe(0);
})->with([
    'no consent at all' => [['consent_given_at' => null, 'consent_by_user_id' => null]],
    'a time but no user' => [['consent_given_at' => '2026-06-15 12:00', 'consent_by_user_id' => null]],
]);

it('saves a child profile with recorded consent', function () {
    $profile = PlayerProfile::factory()->child()->create();

    expect($profile->is_child)->toBeTrue()
        ->and($profile->consent_by_user_id)->toBe($profile->user_id);
});

it('refuses to turn an adult profile into a child one without consent', function () {
    $profile = PlayerProfile::factory()->create(['birth_year' => 1990]);

    expect(fn () => $profile->update(['birth_year' => 2016]))->toThrow(ValidationException::class)
        ->and($profile->fresh()?->birth_year)->toBe(1990);
});
