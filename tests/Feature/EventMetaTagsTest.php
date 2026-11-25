<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

/*
 * M09 AC4: chat apps (WhatsApp, Telegram, Messenger, Discord, iMessage, X)
 * build a link preview from the <meta> tags in the HTML the server sends;
 * they run no JavaScript. The event page's tags carry its title and current
 * count, in Lisbon time, and nothing personal (the privacy test scans the
 * same HTML). Time is frozen at Wed 25 Nov 2026 12:00 UTC.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-25 12:00', 'UTC'));
});

function sharedEvent(array $attributes = [], string $shopName = 'Carta Alta'): Event
{
    return Event::factory()
        ->for(Shop::factory()->create(['name' => $shopName, 'city' => 'Lisboa']))
        ->create(['title' => 'Winter Cup', 'starts_at' => Carbon::parse('2026-11-29 10:00', 'UTC'), ...$attributes]);
}

/** The content of one <meta> tag, by its property or name; null when missing. */
function metaContent(TestResponse $response, string $key): ?string
{
    preg_match('/<meta (?:property|name)="'.preg_quote($key, '/').'" content="([^"]*)"/', (string) $response->getContent(), $match);

    return $match[1] ?? null;
}

it('previews the event with its title, count, day, time and shop', function () {
    $event = sharedEvent();
    Attendance::factory()->count(12)->for($event)->create();

    $response = $this->get("/events/{$event->id}-winter-cup")->assertOk();

    expect(metaContent($response, 'og:title'))->toBe('Winter Cup')
        ->and(metaContent($response, 'og:description'))->toBe('12 trainers going · Sun 29 Nov, 10:00 · Carta Alta, Lisboa')
        ->and(metaContent($response, 'description'))->toBe('12 trainers going · Sun 29 Nov, 10:00 · Carta Alta, Lisboa')
        ->and(metaContent($response, 'og:url'))->toBe(url("/events/{$event->id}-winter-cup"))
        ->and(metaContent($response, 'og:image'))->toBe(url('/og-image.png'))
        ->and(metaContent($response, 'twitter:card'))->toBe('summary_large_image');
});

it('writes the time in Lisbon, an hour ahead of UTC in summer', function () {
    $event = sharedEvent(['starts_at' => Carbon::parse('2027-06-12 09:00', 'UTC')]);

    $response = $this->get("/events/{$event->id}-winter-cup")->assertOk();

    expect(metaContent($response, 'og:description'))->toBe('0 trainers going · Sat 12 Jun, 10:00 · Carta Alta, Lisboa');
});

it('says "trainer" for one', function () {
    $event = sharedEvent();
    Attendance::factory()->for($event)->create();

    $response = $this->get("/events/{$event->id}-winter-cup")->assertOk();

    expect(metaContent($response, 'og:description'))->toStartWith('1 trainer going ·');
});

it('gives a shared ?going link the same preview, pointing at the page itself', function () {
    $event = sharedEvent();

    $response = $this->get("/events/{$event->id}-winter-cup?going")->assertOk();

    expect(metaContent($response, 'og:title'))->toBe('Winter Cup')
        ->and(metaContent($response, 'og:url'))->toBe(url("/events/{$event->id}-winter-cup"));
});

it('escapes the shop name inside the tag', function () {
    $event = sharedEvent(shopName: 'Cards & "Coffee"');

    $response = $this->get("/events/{$event->id}-winter-cup")->assertOk();

    expect(metaContent($response, 'og:description'))->toEndWith('Cards &amp; &quot;Coffee&quot;, Lisboa');
});

it('gives every other page a general preview', function () {
    $response = $this->get(route('home'))->assertOk();

    expect(metaContent($response, 'og:title'))->toBe('Pre-Flight')
        ->and(metaContent($response, 'og:description'))->toBe('See how many trainers are heading to each TCG tournament in Portugal.')
        ->and(metaContent($response, 'og:image'))->toBe(url('/og-image.png'));
});
