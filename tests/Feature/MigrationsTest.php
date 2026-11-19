<?php

use Illuminate\Support\Facades\Schema;

/*
 * AC1: every migration's down() undoes its up(). Later migrations are covered
 * automatically. Schema changes commit MySQL's open transaction; Laravel's
 * rollback checks inTransaction() first, and the test ends fully migrated.
 */
it('rolls every migration back and runs them again', function () {
    $this->artisan('migrate:reset')->assertSuccessful();

    expect(Schema::hasTable('shops'))->toBeFalse()
        ->and(Schema::hasTable('events'))->toBeFalse()
        ->and(Schema::hasTable('import_runs'))->toBeFalse();

    $this->artisan('migrate')->assertSuccessful();

    expect(Schema::hasTable('shops'))->toBeTrue()
        ->and(Schema::hasTable('events'))->toBeTrue()
        ->and(Schema::hasTable('import_runs'))->toBeTrue();
});
