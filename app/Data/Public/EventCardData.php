<?php

namespace App\Data\Public;

use App\Enums\EventType;
use App\Models\Event;

/**
 * One event as other players may see it on a card: no names, Pokemon Player ID (POP ID)s or
 * birth years, only the event and its going count (CLAUDE.md rule 1).
 * Times are UTC ISO strings; the frontend shows them in Lisbon time.
 */
final readonly class EventCardData
{
    public function __construct(
        public int $id,
        public ?string $displayId,
        public EventType $type,
        public string $title,
        public string $shopName,
        public string $city,
        public string $startsAt,
        public string $endsAt,
        public bool $isOngoing,
        public int $goingCount,
        public bool $possiblyCancelled,
    ) {}

    /**
     * Needs the event's shop loaded and its attendances counted (withCount).
     */
    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            displayId: $event->display_id,
            type: $event->type,
            title: $event->title ?? "{$event->type->name} at {$event->shop->name}",
            shopName: $event->shop->name,
            city: $event->shop->city,
            startsAt: $event->starts_at->toIso8601ZuluString(),
            endsAt: $event->ends_at->toIso8601ZuluString(),
            isOngoing: $event->starts_at->lte(now()),
            goingCount: $event->attendances_count,
            possiblyCancelled: $event->possibly_cancelled_at !== null,
        );
    }
}
