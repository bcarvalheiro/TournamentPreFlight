<?php

namespace App\Models;

use Database\Factories\EventFollowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A user following an event: "tell me when 16 are going".
 *
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property int $notify_at_count
 * @property bool $notify_on_drop
 * @property Carbon|null $notified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['notify_at_count', 'notify_on_drop'])]
class EventFollow extends Model
{
    /** @use HasFactory<EventFollowFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify_at_count' => 'integer',
            'notify_on_drop' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

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
