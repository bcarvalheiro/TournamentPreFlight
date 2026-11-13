<?php

/*
 * CLAUDE.md rule 4: times are stored in UTC and shown in Europe/Lisbon.
 * APP_TIMEZONE stays configurable (Laravel's way) but must always be UTC.
 */
it('runs the application in UTC', function () {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(date_default_timezone_get())->toBe('UTC');
});
