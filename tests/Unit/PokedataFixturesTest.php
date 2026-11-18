<?php

/**
 * Raw pokedata.ovh responses saved in the M03 spike. The M10 importer tests
 * feed them to Http::fake(), so they must exist and stay valid JSON.
 */
$fixtures = glob(__DIR__.'/../Fixtures/pokedata/*.json') ?: [];

it('has pokedata fixtures', function () use ($fixtures) {
    expect($fixtures)->not->toBeEmpty();
});

it('stores every pokedata fixture as valid JSON', function (string $path) {
    expect(file_get_contents($path))->toBeJson();
})->with($fixtures);

it('has at least one event in the upcoming fixture', function () {
    $events = json_decode(
        file_get_contents(__DIR__.'/../Fixtures/pokedata/pt-tcg-upcoming-2026-11-18.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($events)->not->toBeEmpty();
});
