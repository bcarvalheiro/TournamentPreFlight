<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventReport;

/*
 * Events are normally hidden or cancelled, not deleted. When an admin does
 * delete one (M11), its answers and reports go with it, and nothing
 * belonging to another event is touched.
 */
it('deletes an event\'s answers and reports with it', function () {
    [$deleted, $kept] = Event::factory()->count(2)->create();

    foreach ([$deleted, $kept] as $event) {
        Attendance::factory()->for($event)->create();
        EventReport::factory()->for($event)->create();
    }

    $deleted->delete();

    expect([
        'attendances' => Attendance::query()->pluck('event_id')->all(),
        'reports' => EventReport::query()->pluck('event_id')->all(),
    ])->toBe([
        'attendances' => [$kept->id],
        'reports' => [$kept->id],
    ]);
});
