<?php

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;

it('blocks unfaked outgoing HTTP requests', function () {
    // Checked first so a broken guard fails here, before any real request is made.
    expect(Http::preventingStrayRequests())->toBeTrue();

    Http::get('https://example.com');
})->throws(StrayRequestException::class);
