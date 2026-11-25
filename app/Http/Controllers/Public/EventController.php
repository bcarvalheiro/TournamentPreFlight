<?php

namespace App\Http\Controllers\Public;

use App\Data\Private\OwnAttendanceData;
use App\Data\Public\EventPageData;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * The event page (M09), at `/events/{id}-{slug}`. A missing or old slug
     * redirects permanently to the current one, keeping the query string so a
     * shared `?going` link still opens its pop-up.
     */
    public function show(Request $request, Event $event, ?string $slug = null): Response|RedirectResponse
    {
        $event->load('shop:id,name,address,district,city,latitude,longitude');

        if ($slug !== $event->slug()) {
            // The raw query string: getQueryString() would turn `?going` into `?going=`.
            $query = $request->server->getString('QUERY_STRING');
            $canonical = route('events.show', ['event' => $event, 'slug' => $event->slug()]);

            return redirect()->to($query === '' ? $canonical : "{$canonical}?{$query}", 301);
        }

        $event->loadCount([
            'attendances',
            'attendances as confirmed_count' => fn (Builder $query) => $query->whereNotNull('confirmed_at'),
        ]);

        $page = EventPageData::fromModel($event);

        return Inertia::render('events/show', [
            'event' => $page,
            // A shared `?going` link asks "Going to …?" (M09 AC6, decisions.md 23 Nov);
            // only on an event people can still answer. Opening it writes nothing.
            'askGoing' => $request->query->has('going') && $page->canAnswer,
            // The account's own trainers and answers (M09 Slice 5), for its eyes only and
            // only while answers are open; a closure, so a partial reload computes it only
            // when asked for. Anyone else's "Going?" uses the pending intent (M06).
            'own' => fn () => $page->canAnswer && $request->user()?->isPlayerReady()
                ? OwnAttendanceData::forUser($request->user())
                : null,
        ])->withViewData(['meta' => $this->linkPreview($event, $page)]);
    }

    /**
     * What chat apps show when the page is shared (M09 AC4): the title and
     * "12 trainers going · Sat 5 Dec, 10:00 · Carta Alta, Porto", in Lisbon
     * time (rule 4), counts only (rule 1). The root template writes them as
     * Open Graph tags; previews read the HTML and run no JavaScript.
     *
     * @return array{title: string, description: string, url: string}
     */
    private function linkPreview(Event $event, EventPageData $page): array
    {
        $going = trans_choice(':count trainer going|:count trainers going', $page->goingCount);
        $when = $event->starts_at->copy()->tz('Europe/Lisbon')->format('D j M, H:i');

        return [
            'title' => $page->title,
            'description' => "{$going} · {$when} · {$page->shopName}, {$page->city}",
            // The page itself, also for a shared ?going link.
            'url' => $page->url,
        ];
    }
}
