<?php

use App\Actions\CreatePlayerProfile;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07 (takeover, M06 review): when the owner proves an email someone else
 * registered, that person's "I am 13 or older" is not the owner's answer. It
 * is cleared, and "Add a trainer" asks it again before the first trainer.
 */
function resetPassword(User $user): void
{
    test()->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'owners-password',
        'password_confirmation' => 'owners-password',
    ])->assertSessionHasNoErrors();
}

function trainer(array $overrides = []): array
{
    return ['name' => 'Ana Lima', 'pop_id' => '8888888', 'birth_year' => 1990, ...$overrides];
}

it('clears the claimer\'s age answer when the owner takes the account over', function () {
    $claimed = User::factory()->unverified()->create();
    resetPassword($claimed);

    expect($claimed->fresh()->age_confirmed_at)->toBeNull();
});

it('keeps the age answer on a normal password reset', function () {
    $user = User::factory()->create();
    resetPassword($user);

    expect($user->fresh()->age_confirmed_at)->not->toBeNull();
});

it('asks the age on "Add a trainer" only when the account has no answer', function () {
    $this->actingAs(User::factory()->create(['age_confirmed_at' => null]))
        ->get(route('profiles.create'))
        ->assertInertia(fn (Assert $page) => $page->where('asksAge', true));

    $this->actingAs(User::factory()->create())
        ->get(route('profiles.create'))
        ->assertInertia(fn (Assert $page) => $page->where('asksAge', false));
});

it('refuses the first trainer without the age box', function () {
    $owner = User::factory()->create(['age_confirmed_at' => null]);

    $this->actingAs($owner)->post(route('profiles.store'), trainer())
        ->assertSessionHasErrors(['age_confirmed' => 'Please confirm you are 13 or older.']);

    expect($owner->playerProfiles()->exists())->toBeFalse()
        ->and($owner->fresh()->age_confirmed_at)->toBeNull();
});

it('records the owner\'s answer with the first trainer', function () {
    $this->freezeSecond();
    $owner = User::factory()->create(['age_confirmed_at' => null]);

    $this->actingAs($owner)->post(route('profiles.store'), trainer(['age_confirmed' => '1']))
        ->assertSessionHasNoErrors();

    expect($owner->playerProfiles()->count())->toBe(1)
        ->and($owner->fresh()->age_confirmed_at->equalTo(now()))->toBeTrue();
});

// Review fix: the age answer and the trainer are saved together or not at all.
it('keeps no age answer when the trainer cannot be saved', function () {
    PlayerProfile::factory()->create(['pop_id' => '8888888']);
    $owner = User::factory()->create(['age_confirmed_at' => null]);

    // Straight to the action: a duplicate the form request did not catch (two tabs at once).
    expect(fn () => app(CreatePlayerProfile::class)->handle($owner, trainer(['age_confirmed' => '1'])))
        ->toThrow(QueryException::class);

    expect($owner->fresh()->age_confirmed_at)->toBeNull();
});
