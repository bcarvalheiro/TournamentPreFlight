<?php

namespace App\Http\Controllers\Public;

use App\Data\Public\EventCardData;
use App\Http\Controllers\Controller;
use App\Models\Event;
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
    public function __invoke(): Response
    {
        $upcoming = Event::upcoming()
            ->with('shop:id,name,city')
            ->withCount('attendances');

        return Inertia::render('home', [
            'events' => $upcoming->clone()->limit(self::LIST_CAP)->get()->map(EventCardData::fromModel(...)),
            'upNext' => $upcoming->clone()->limit(self::UP_NEXT_SIZE)->get()->map(EventCardData::fromModel(...)),
            'totals' => [
                'matched' => Event::upcoming()->count(),
                'all' => Event::upcoming()->count(),
            ],
        ]);
    }
}
