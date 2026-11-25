<?php

namespace App\Data\Public;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;

/**
 * One event as anyone may see it on its page (M09): details, counts and state,
 * never a name, Pokemon Player ID (POP ID) or birth year (CLAUDE.md rule 1).
 * Times are UTC ISO strings; the frontend shows them in Lisbon time.
 */
final readonly class EventPageData
{
    public function __construct(
        public int $id,
        public string $url,
        public ?string $displayId,
        public EventType $type,
        public string $title,
        public string $shopName,
        public ?string $address,
        public ?string $district,
        public string $city,
        public string $startsAt,
        public string $endsAt,
        public ?string $officialUrl,
        public int $goingCount,
        public int $confirmedCount,
        public bool $isOngoing,
        public bool $hasEnded,
        public bool $isCancelled,
        public bool $possiblyCancelled,
        public bool $canAnswer,
    ) {}

    /**
     * Needs the event's shop loaded and its attendances counted as
     * `attendances_count` and `confirmed_count` (loadCount).
     */
    public static function fromModel(Event $event): self
    {
        $hasStarted = $event->starts_at->lte(now());
        $hasEnded = $event->ends_at->lte(now());

        return new self(
            id: $event->id,
            url: route('events.show', ['event' => $event, 'slug' => $event->slug()]),
            displayId: $event->display_id,
            type: $event->type,
            title: $event->displayTitle(),
            shopName: $event->shop->name,
            address: $event->shop->address,
            district: $event->shop->district,
            city: $event->shop->city,
            startsAt: $event->starts_at->toIso8601ZuluString(),
            endsAt: $event->ends_at->toIso8601ZuluString(),
            officialUrl: $event->official_url,
            goingCount: $event->attendances_count,
            confirmedCount: $event->confirmed_count,
            isOngoing: $hasStarted && ! $hasEnded,
            hasEnded: $hasEnded,
            isCancelled: $event->status === EventStatus::Cancelled,
            possiblyCancelled: $event->possibly_cancelled_at !== null,
            // The rule SetAttendance enforces (the `upcoming` scope): listed and not ended.
            canAnswer: $event->status === EventStatus::Listed && ! $hasEnded,
        );
    }
}
