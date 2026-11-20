<?php

namespace App\Models;

use Database\Factories\EventReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A player's "Report a change or cancellation" for an event.
 *
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property string $type changed, cancelled or wrong
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'message'])]
class EventReport extends Model
{
    /** @use HasFactory<EventReportFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
