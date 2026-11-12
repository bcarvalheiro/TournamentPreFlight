<?php

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;

it('blocks unfaked outgoing HTTP requests', function () {
    Http::get('https://example.com');
})->throws(StrayRequestException::class);
