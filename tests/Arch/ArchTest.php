<?php

use App\Models\PlayerProfile;
use App\Models\User;

arch()->preset()->php();

arch()->preset()->security();

arch('no debugging functions')
    ->expect(['dd', 'dump', 'ray', 'ddd', 'var_dump'])
    ->not->toBeUsed();

/*
 * Rule 1 (CLAUDE.md, "Counts only"): public pages send only App\Data\Public
 * DTOs, and neither they nor the public controllers touch player data.
 * NoPersonalDataLeaksTest checks what the pages actually send.
 */
arch('public controllers never touch player data')
    ->expect('App\Http\Controllers\Public')
    ->not->toUse([User::class, PlayerProfile::class]);

arch('public data are immutable DTOs without player data')
    ->expect('App\Data\Public')
    ->toBeFinal()
    ->toBeReadonly()
    ->not->toUse([User::class, PlayerProfile::class]);

/*
 * Owner-only data (M07, M08): only the signed-in account's own pages build it,
 * plus the owner-only `own` prop on the public home page (review fix) and
 * event page (M09).
 * A new public controller cannot pick these classes up without changing this rule.
 */
arch('owner-only data is built only for the account\'s own pages')
    ->expect('App\Data\Private')
    ->toOnlyBeUsedIn([
        'App\Http\Controllers\Account',
        'App\Http\Controllers\Public\HomeController',
        'App\Http\Controllers\Public\EventController',
    ]);
