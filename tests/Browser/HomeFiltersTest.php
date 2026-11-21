<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;

/*
 * M05 AC8: at phone width the filter sheet opens from the bar, and ticking
 * Cup and a shop updates the list and the URL at once (apply as you tick).
 * Opening that URL fresh keeps the filters; a chip removes its own filter
 * and Clear all removes them all. The count line in the bar
 * stands for the list: Up next always shows every event, so shop names
 * stay on the page whatever the filters.
 */
it('filters by type and shop from the sheet, keeps them in the URL, and clears them', function () {
    $alfa = Shop::factory()->create(['name' => 'Alfa']);
    $zeta = Shop::factory()->create(['name' => 'Zeta']);
    Event::factory()->create(['shop_id' => $alfa->id, 'type' => EventType::Cup, 'starts_at' => now()->addDays(2)]);
    Event::factory()->create(['shop_id' => $zeta->id, 'type' => EventType::Cup, 'starts_at' => now()->addDays(3)]);
    Event::factory()->create(['shop_id' => $alfa->id, 'type' => EventType::Casual, 'starts_at' => now()->addDays(4)]);

    $page = visit('/')->resize(390, 844);

    $page->assertSee('3 of 3 events')
        ->click('@open-filters')
        ->click('@type-cup')
        ->assertSee('Show 2 events')
        ->click("@shop-{$alfa->id}")
        ->assertSee('Show 1 event')
        ->click('Show 1 event')
        ->assertSee('1 of 3 events');

    // Read the query string as Laravel does: it writes arrays as type[0]=cup.
    $queryString = (string) parse_url($page->url(), PHP_URL_QUERY);
    parse_str($queryString, $query);
    expect($query)->toMatchArray(['type' => ['cup'], 'shop' => [(string) $alfa->id]]);

    $page->navigate('/?'.$queryString)
        ->assertSee('1 of 3 events')
        ->assertPresent('[aria-label="Remove Cup filter"]')
        ->assertPresent('[aria-label="Remove Alfa filter"]');

    // A chip removes only its own filter; Clear all removes every filter.
    $page->click('[aria-label="Remove Alfa filter"]')
        ->assertSee('2 of 3 events')
        ->assertNotPresent('[aria-label="Remove Alfa filter"]')
        ->click('@open-filters')
        ->click('Clear all')
        ->assertSee('Show 3 events')
        ->click('Show 3 events')
        ->assertSee('3 of 3 events')
        ->assertScript('location.search === ""')
        ->assertNoJavaScriptErrors();
});

it('keeps every tick when ticking quickly', function () {
    $alfa = Shop::factory()->create(['name' => 'Alfa']);
    Event::factory()->create(['shop_id' => $alfa->id, 'type' => EventType::Cup, 'starts_at' => now()->addDays(2)]);
    Event::factory()->create(['shop_id' => $alfa->id, 'type' => EventType::Challenge, 'starts_at' => now()->addDays(3)]);
    Event::factory()->create(['shop_id' => $alfa->id, 'type' => EventType::Casual, 'starts_at' => now()->addDays(4)]);

    // A slow server, like a phone on mobile data: the second tick goes out
    // before the first answer is back. The browser server runs in this process.
    DB::listen(fn () => usleep(150_000));

    $page = visit('/')->resize(390, 844)
        ->click('@open-filters')
        ->click('@type-cup')
        ->click('@type-challenge')
        ->assertSee('Show 2 events');

    parse_str((string) parse_url($page->url(), PHP_URL_QUERY), $query);
    expect($query['type'] ?? [])->toEqualCanonicalizing(['cup', 'challenge']);
});
