<?php

use Symfony\Component\Finder\Finder;

/*
 * The arch() tests only inspect classes. Routes, config, bootstrap files and
 * Blade views are plain files, so a forgotten dd() there would slip through.
 * This test reads their text instead.
 */
it('has no debugging calls in routes, config, bootstrap or views', function () {
    $root = dirname(__DIR__, 2);

    $files = Finder::create()
        ->files()
        ->in([
            $root.'/routes',
            $root.'/config',
            $root.'/bootstrap',
            $root.'/resources/views',
        ])
        ->exclude(['cache', 'ssr'])
        ->name('*.php');

    // dd( dump( ray( ddd( var_dump( as function calls (not ->dump() or $dump()),
    // plus the Blade directives @dd and @dump.
    $debugCall = '/(?<![\w>:$])(dd|ddd|dump|ray|var_dump)\s*\(|@(dd|dump)\b/';

    $offenders = [];

    foreach ($files as $file) {
        if (preg_match($debugCall, $file->getContents())) {
            $offenders[] = str_replace($root.'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});
