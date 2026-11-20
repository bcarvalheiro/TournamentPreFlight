<?php

namespace App\Http\Controllers\Public;

use App\Data\Public\EventCardData;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeFilterRequest;
use App\Models\Event;
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
     * `upNext` never follows the filters; `totals` counts beyond the cap ("First 25 of 40").
     */
    public function __invoke(HomeFilterRequest $request): Response
    {
        $types = $request->types();
        $shopIds = $request->shopIds();
        $dateRange = $request->dateRange();

        // The scopes match nothing for an empty list, so a filter applies only when chosen.
        $matching = Event::upcoming()
            ->when($types, fn (Builder $query) => $query->ofTypes($types))
            ->when($shopIds, fn (Builder $query) => $query->atShops($shopIds))
            ->when($dateRange, fn (Builder $query) => $query->between(...$dateRange));

        return Inertia::render('home', [
            'events' => $this->cards($matching->clone()->limit(self::LIST_CAP)),
            'upNext' => $this->cards(Event::upcoming()->limit(self::UP_NEXT_SIZE)),
            'totals' => [
                'matched' => $matching->count(),
                'all' => Event::upcoming()->count(),
            ],
            'filters' => [
                'type' => $types,
                'shop' => $shopIds,
                'when' => $request->datePreset(),
                'from' => $request->from()?->toDateString(),
                'to' => $request->to()?->toDateString(),
            ],
        ]);
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
