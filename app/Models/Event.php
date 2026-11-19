<?php

namespace App\Models;

use App\Enums\EventSourceKey;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property EventSourceKey $source
 * @property string|null $official_guid
 * @property string|null $official_url
 * @property string|null $display_id
 * @property EventType $type
 * @property string|null $title
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property EventStatus $status
 * @property Carbon|null $possibly_cancelled_at
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $changed_at
 * @property int|null $added_by_user_id
 * @property string|null $moderation_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'shop_id', 'source', 'official_guid', 'official_url', 'display_id', 'type', 'title',
    'starts_at', 'status', 'possibly_cancelled_at', 'last_seen_at', 'changed_at',
    'added_by_user_id', 'moderation_note',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * ends_at is always the type's estimate, recomputed on every save
     * so a moved event or a changed type never keeps an old end.
     */
    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            $event->ends_at = $event->starts_at->copy()->addHours($event->type->estimatedHours());
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => EventSourceKey::class,
            'type' => EventType::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'possibly_cancelled_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
