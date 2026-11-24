<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued by `SetAttendance` after each change to an event's answers
 * (architecture.md, Notifications). In M12 it emails the followers whose
 * "tell me when n are going" threshold is reached; until then it does nothing.
 */
class CheckThresholds implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event) {}

    /**
     * Nothing yet: the threshold emails come in M12.
     */
    public function handle(): void {}
}
