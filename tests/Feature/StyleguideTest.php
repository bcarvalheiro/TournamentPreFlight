<?php

use Inertia\Testing\AssertableInertia as Assert;

/*
 * The styleguide is a developer page: it exists only in the local environment.
 */
it('renders the styleguide in the local environment', function () {
    registerWebRoutesIn('local');

    $response = $this->get('/styleguide');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('styleguide'));
});

it('returns 404 for the styleguide outside the local environment', function (string $environment) {
    registerWebRoutesIn($environment);

    $response = $this->get('/styleguide');

    $response->assertNotFound();
})->with(['production', 'staging', 'testing']);
