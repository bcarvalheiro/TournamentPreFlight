<?php

use App\Models\Event;
use App\Models\Shop;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * AC2: the database itself refuses duplicates, so a second import of the same
 * pokedata event, or a second submission of the same locator link, can never
 * create a second row.
 */
it('refuses two events with the same official guid', function () {
    Event::factory()->create(['official_guid' => '0aaf9ad5-8100-42c8-ae2c-98ab878999be']);

    expect(fn () => Event::factory()->create(['official_guid' => '0aaf9ad5-8100-42c8-ae2c-98ab878999be']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows several events without an official guid', function () {
    Event::factory()->count(2)->create(['official_guid' => null]);

    expect(Event::query()->whereNull('official_guid')->count())->toBe(2);
});

it('refuses two events with the same official url', function () {
    Event::factory()->create(['official_url' => 'https://events.pokemon.com/EventLocator/Details/26-11-001729']);

    expect(fn () => Event::factory()->create(['official_url' => 'https://events.pokemon.com/EventLocator/Details/26-11-001729']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('refuses two shops with the same official name key', function () {
    Shop::factory()->create(['official_name_key' => 'dado dourado|lisboa']);

    expect(fn () => Shop::factory()->create(['official_name_key' => 'dado dourado|lisboa']))
        ->toThrow(UniqueConstraintViolationException::class);
});
