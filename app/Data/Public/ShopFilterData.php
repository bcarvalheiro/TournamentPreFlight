<?php

namespace App\Data\Public;

use App\Models\Shop;

/**
 * One host shop in the home page's shop filter, with how many of its
 * upcoming events match the other filters.
 */
final readonly class ShopFilterData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $city,
        public int $eventCount,
    ) {}

    /**
     * Needs the shop's events counted (withCount).
     */
    public static function fromModel(Shop $shop): self
    {
        return new self(
            id: $shop->id,
            name: $shop->name,
            city: $shop->city,
            eventCount: $shop->events_count,
        );
    }
}
