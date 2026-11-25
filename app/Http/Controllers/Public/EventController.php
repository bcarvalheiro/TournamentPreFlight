<?php

namespace App\Http\Controllers\Public;

use App\Data\Private\OwnAttendanceData;
use App\Data\Public\EventPageData;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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

        $page = $this->pageData($event);

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
     * "Add to calendar" (M09 Slice 10): the event as an iCalendar file (RFC
     * 5545) for Apple Calendar and Outlook. Times in UTC (rule 4), the end is
     * the type's estimate, and the description links back to the page. Built
     * from EventPageData like the page, so no player data (rule 1).
     */
    public function calendar(Event $event): HttpResponse
    {
        $event->load('shop:id,name,address,district,city,latitude,longitude');
        $page = $this->pageData($event);
        // "2026-11-29T10:00:00Z" → "20261129T100000Z", the UTC form calendars read.
        $utc = fn (string $iso): string => str_replace(['-', ':'], '', $iso);
        $place = implode(', ', array_filter([$page->shopName, $page->address, $page->city]));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Pre-Flight//Events//EN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:event-{$page->id}@".parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$utc(now()->toIso8601ZuluString()),
            'DTSTART:'.$utc($page->startsAt),
            'DTEND:'.$utc($page->endsAt),
            'SUMMARY:'.$this->calendarText($page->title),
            'LOCATION:'.$this->calendarText($place),
            'DESCRIPTION:'.$this->calendarText("Who's going: {$page->url}\nA headcount, not a registration. Register with the shop as usual."),
            "URL:{$page->url}",
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", array_map($this->foldCalendarLine(...), $lines))."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$event->slug()}.ics\"",
        ]);
    }

    /**
     * The public view of an event (rule 1), with the going and confirmed
     * counts; the shop must be loaded.
     */
    private function pageData(Event $event): EventPageData
    {
        $event->loadCount([
            'attendances',
            'attendances as confirmed_count' => fn (Builder $query) => $query->whereNotNull('confirmed_at'),
        ]);

        return EventPageData::fromModel($event);
    }

    /** RFC 5545 text: a backslash before `\`, `;` and `,`, and `\n` for a new line. */
    private function calendarText(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $text);
    }

    /**
     * RFC 5545 folds lines longer than 75 bytes: the rest goes on the next
     * line after one space. Cut on whole characters so accents stay intact.
     */
    private function foldCalendarLine(string $line): string
    {
        $parts = [mb_strcut($line, 0, 75)];

        for ($offset = strlen($parts[0]); $offset < strlen($line); $offset += strlen(end($parts))) {
            $parts[] = mb_strcut($line, $offset, 74);
        }

        return implode("\r\n ", $parts);
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
