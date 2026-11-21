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
