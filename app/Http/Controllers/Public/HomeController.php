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

    /**
     * The home page: upcoming and ongoing events, soonest first, as public cards only.
     */
    public function __invoke(): Response
    {
        $events = Event::upcoming()
            ->with('shop:id,name,city')
            ->withCount('attendances')
            ->limit(self::LIST_CAP)
            ->get();

        return Inertia::render('home', [
            'events' => $events->map(EventCardData::fromModel(...)),
        ]);
    }
}
