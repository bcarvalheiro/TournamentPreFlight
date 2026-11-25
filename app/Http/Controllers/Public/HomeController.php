<?php

namespace App\Http\Controllers\Public;

use App\Data\Private\OwnAttendanceData;
use App\Data\Public\EventCardData;
use App\Data\Public\ShopFilterData;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeFilterRequest;
use App\Models\Event;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /** The longest list the home page shows (product.md, Home page). */
    private const int LIST_CAP = 25;

    /** How many events the pairings sheet ("Up next") shows. */
    private const int UP_NEXT_SIZE = 5;

    /**
     * The home page: upcoming and ongoing events, soonest first, as public cards only.
     * `upNext` never follows the filters; `totals` counts beyond the cap ("First 25 of 40");
     * `shops` lists the shops for the filter sheet; a chosen shop not in it is dropped.
     */
    public function __invoke(HomeFilterRequest $request): Response
    {
        $types = $request->types();
        $dateRange = $request->dateRange();

        // Shops with any upcoming event; the counts follow type and date, never the shop filter.
        $shops = Shop::query()
            ->whereHas('events', fn (Builder $query) => $query->upcoming())
            ->withCount(['events' => fn (Builder $query) => $this->upcomingOfTypeAndDate($query, $types, $dateRange)])
            ->orderBy('name')
            ->get();
        $shopIds = array_values(array_intersect($request->shopIds(), $shops->modelKeys()));
        $pickedDates = $request->datePreset() === 'custom';

        $matching = $this->upcomingOfTypeAndDate(Event::query(), $types, $dateRange)
            ->when($shopIds, fn (Builder $query) => $query->atShops($shopIds));

        return Inertia::render('home', [
            'events' => $this->cards($matching->clone()->limit(self::LIST_CAP)),
            'upNext' => $this->cards(Event::upcoming()->limit(self::UP_NEXT_SIZE)),
            'totals' => [
                'matched' => $matching->count(),
                'all' => Event::upcoming()->count(),
            ],
            'shops' => $shops->map(ShopFilterData::fromModel(...)),
            // The account's own trainers and answers (M08), for its eyes only; a closure,
            // so a partial reload computes it only when asked for. Only for a player
            // (verified email and a trainer): anyone else's button uses the pending intent.
            'own' => fn () => $request->user()?->isPlayerReady() ? OwnAttendanceData::forUser($request->user()) : null,
            'filters' => [
                'type' => $types,
                'shop' => $shopIds,
                'when' => $request->datePreset(),
                'from' => $pickedDates ? $request->from()?->toDateString() : null,
                'to' => $pickedDates ? $request->to()?->toDateString() : null,
            ],
        ]);
    }

    /**
     * Upcoming events of the chosen types and dates; shared by the event list
     * and the shop counts. The scopes match nothing for an empty list, so a
     * filter applies only when chosen.
     *
     * @param  Builder<Event>  $query
     * @param  list<EventType>  $types
     * @param  array{CarbonImmutable, CarbonImmutable|null}|null  $dateRange
     * @return Builder<Event>
     */
    private function upcomingOfTypeAndDate(Builder $query, array $types, ?array $dateRange): Builder
    {
        return $query->upcoming()
            ->when($types, fn (Builder $query) => $query->ofTypes($types))
            ->when($dateRange, fn (Builder $query) => $query->between(...$dateRange));
    }

    /**
     * The events as public cards, each with its shop and going count.
     *
     * @param  Builder<Event>  $query
     * @return Collection<int, EventCardData>
     */
    private function cards(Builder $query): Collection
    {
        return $query->with('shop:id,name,city')
            ->withCount('attendances')
            ->get()
            ->map(EventCardData::fromModel(...));
    }
}
