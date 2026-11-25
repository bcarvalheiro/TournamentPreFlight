<?php

namespace App\Http\Controllers\Account;

use App\Actions\SetAttendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

/**
 * The signed-in account's "I'm going" answers (M08).
 */
class AttendanceController extends Controller
{
    /**
     * Saves the answers for one or several trainers in one call (all or
     * nothing, see `SetAttendance`), then back to the page.
     */
    public function update(AttendanceRequest $request, Event $event, SetAttendance $setAttendance): RedirectResponse
    {
        /** @var array<int, mixed> $answers validated booleans: true, false, 1, 0, "1" or "0" */
        $answers = $request->validated('profiles');

        $setAttendance->handle($request->user(), $event, array_map(boolval(...), $answers));

        return back();
    }
}
