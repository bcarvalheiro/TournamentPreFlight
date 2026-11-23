<?php

use Inertia\Testing\AssertableInertia as Assert;

/*
 * M06: the sign-in and register pages offer only the social providers whose
 * credentials are configured, so a button never leads to a broken sign-in.
 */
beforeEach(function () {
    config([
        'services.google.client_id' => 'google-id',
        'services.discord.client_id' => null,
        'services.x.client_id' => '',
    ]);
});

it('offers the configured providers on the sign-in page', function () {
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->where('socialProviders', ['google']));
});

it('offers the configured providers on the register page', function () {
    $this->get(route('register'))->assertInertia(fn (Assert $page) => $page
        ->where('socialProviders', ['google']));
});
