<?php

namespace App\Models;

use App\Enums\EventSourceKey;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
 * @property-read int $confirmed_count only after loadCount(["attendances as confirmed_count" => …]) (M09)
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
     * Listed events that have not ended yet (ongoing ones included), soonest first,
     * ties by id.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('status', EventStatus::Listed)
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->orderBy('id'); // equal starts would otherwise come back in any order
    }

    /**
     * Events of any of the given types. An empty list matches nothing.
     *
     * @param  Builder<Event>  $query
     * @param  list<EventType>  $types
     */
    #[Scope]
    protected function ofTypes(Builder $query, array $types): void
    {
        $query->whereIn('type', $types);
    }

    /**
     * Events at any of the given shops. An empty list matches nothing.
     *
     * @param  Builder<Event>  $query
     * @param  list<int>  $shopIds
     */
    #[Scope]
    protected function atShops(Builder $query, array $shopIds): void
    {
        $query->whereIn('shop_id', $shopIds);
    }

    /**
     * Events starting between the two moments, both included; without $to the
     * range has no end. The bounds may be in any time zone (M05 passes Lisbon
     * days): they are converted to UTC, the zone starts_at is stored in,
     * because Laravel writes a date's clock time into SQL without converting it.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function between(Builder $query, CarbonInterface $from, ?CarbonInterface $to): void
    {
        $query->where('starts_at', '>=', $from->copy()->utc())
            ->when($to, fn (Builder $query) => $query->where('starts_at', '<=', $to->copy()->utc()));
    }

    /**
     * The title players see: the source's own, or the type and shop when it has none
     * ("League Cup at Carta Alta"; pokedata.md, 18 Nov 2026). Needs the shop.
     */
    public function displayTitle(): string
    {
        return $this->title ?? "{$this->type->longName()} at {$this->shop->name}";
    }

    /**
     * The readable part of the event page URL ("winter-cup"). Never stored: a
     * changed title gives a new slug, and the old URL redirects (M09).
     */
    public function slug(): string
    {
        return Str::slug($this->displayTitle());
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

    /**
     * Going answers for this event (one row per going profile).
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
