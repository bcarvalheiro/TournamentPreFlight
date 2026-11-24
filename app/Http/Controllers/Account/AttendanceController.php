<?php

namespace App\Http\Controllers\Account;

use App\Actions\SetAttendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Event;
use App\Models\PlayerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in account's "I'm going" answers (M08).
 */
class AttendanceController extends Controller
{
    /**
     * Saves the answers for several trainers at once, all or nothing: one
     * refused trainer rolls the whole request back. Then back to the page.
     */
    public function update(AttendanceRequest $request, Event $event, SetAttendance $setAttendance): RedirectResponse
    {
        /** @var array<int, bool> $answers */
        $answers = $request->validated('profiles');
        $profiles = PlayerProfile::query()->findOrFail(array_keys($answers));

        DB::transaction(function () use ($profiles, $request, $event, $setAttendance) {
            foreach ($profiles as $profile) {
                $setAttendance->handle($request->user(), $profile, $event, $request->boolean("profiles.{$profile->id}"));
            }
        });

        return back();
    }
}
