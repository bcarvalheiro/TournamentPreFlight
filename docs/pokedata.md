# pokedata.ovh

What pokedata.ovh returns for Portugal, learned from real requests in the M03 spike. The importer (M10) follows this file.

- Public API help: https://pokedata.ovh/events/api/help
- Fixtures: `tests/Fixtures/pokedata/` (raw responses, unedited)

## Request contract

The importer makes **one** request:

```
GET https://pokedata.ovh/events/api/_tcg/_country/PT
```

- No `_start`: the API starts at today by default. No `_end`: it returns everything it knows ahead (on 18 Nov 2026, up to 28 Feb 2027).
- One response holds every event (850 on 18 Nov 2026). There is no paging in the public API.
- Response: `200`, `application/json`, a plain JSON list of event objects. No events gives `[]`.
- **The request alone is not enough: the importer must filter the response** (next section).

## Filter contract

A bare `_tcg` filters nothing. Its response is byte-for-byte identical to `_country/PT` (630,869 bytes, 18 Nov 2026; repeated twice, `cf-cache-status: DYNAMIC`, so not a cached copy; the order `_country/PT/_tcg` gives the same). It contains video game (VG) and GO events.

Keep an event only if one of these holds:

| `type` | `product` | Keep as |
| --- | --- | --- |
| `nonpremier TCG` | (field absent) | friendly |
| `League Challenge` | `tcg` | challenge |
| `League Cup` | `tcg` | cup |
| prerelease (name not seen yet, see below) | to confirm | prerelease |

Drop everything else, including:

| `type` | `product` | Count on 18 Nov 2026 |
| --- | --- | --- |
| `nonpremier VG` | (field absent) | 22 |
| `League Challenge` | `vg` / `go` | 6 / 2 |
| `League Cup` | `vg` / `go` | 2 / 2 |

`product` exists only on League Challenge and League Cup. **Filtering on `type` alone would import 12 VG and GO tournaments as TCG.**

Counts kept on 18 Nov 2026: 777 friendly, 29 challenge, 10 cup (816 of 850).

### Why not the narrowed request

`_tcg/cups/challenges/pre/_country/PT` does filter by product: it returned exactly the 39 cups and challenges with `product: tcg`. But the API has no sub-option for friendlies, and `_tcg/ftcg/_country/PT` (the website's name for friendlies) returns `[]`: an unknown sub-option matches nothing. So friendlies come only from the bare `_tcg`, which needs the filter above.

## Tournament types

| pokedata `type` | Website label | Our type |
| --- | --- | --- |
| `nonpremier TCG` | TCG Friendly | friendly |
| `League Challenge` (`product: tcg`) | TCG League Challenge | challenge |
| `League Cup` (`product: tcg`) | TCG League Cup | cup |
| not seen yet | Pre Release | prerelease |

- **Prerelease** is a type of its own (Bruno, 18 Nov 2026). None were listed on 18 Nov because prereleases only run before a new set is released; the website showed 0. Its `type` and `product` values are unknown until one appears: check before relying on the mapping.
- **League** has no type in pokedata. League nights may arrive as `nonpremier TCG`. Open for Bruno.
- The website also has `premier` and `mss` filters (see below). Neither appeared in the Portugal data; values unknown.

## Event-specific URL and tournament ID

`pokemon_url` is either:

- event-specific, ending in the official tournament ID: `https://www.pokemon.com/us/pokemon-trainer-club/play-pokemon-tournaments/26-11-001729/`. All 51 cups and challenges and 53 of the 777 friendlies on 18 Nov 2026;
- or the generic locator page `https://www.pokemon.com/us/pokemon-trainer-club/play-pokemon-tournaments//` (note the double slash), carrying no information. Treat it as missing.

Every event has a `guid` (for example `0aaf9ad5-8100-42c8-ae2c-98ab878999be`), the same value the website uses.

## The website's own endpoint (not used)

The pokedata.ovh website does not call the public API. It sends:

```
POST https://www.pokedata.ovh/events/tableapi/index_table.php
Content-Type: application/json

{"past":false,"country":"PT","city":"","shop":"","league":"","states":"[]","postcode":"",
 "cups":true,"challenges":true,"vcups":false,"vchallenges":false,"prereleases":true,
 "premier":false,"go":false,"gocup":false,"mss":false,"ftcg":true,"fvg":false,"fgo":false,
 "latitude":"","longitude":"","radius":"","unit":"km","width":841,"page":0}
```

(all four TCG boxes ticked; `ftcg` is Friendly TCG). The response is JSON labelled `text/html`, 100 events per page, and each event has only `guid`, `type` and `data` (an HTML snippet: `<b>2026-11-18 15:00 - TCG Friendly</b><br />@ DECKOUT<br />Almancil - Faro<br />PT`).

Compared with the public API on 18 Nov 2026: all 100 events on page 0 are in the API response. The 13 API events in the same date range missing from page 0 are 9 VG/GO cups and challenges (rightly excluded) and 4 friendlies at the page boundary (26 Nov 15:00). Same data, so the website is the API minus non-TCG events.

**Not used**: undocumented, internal to the website, paged, and the details are inside HTML (we do not scrape; `decisions.md`, 12 Nov 2026).

## Oddities

- A League Challenge starting at `03:00:00` (22 Nov 2026), probably a data entry error.
- `state` is not standardised (`Lisboa` and `Lisbon` both appear on the website), as the API help warns. We filter by country only.
- The website warns: "Events are retrieved once (but some data is updated afterwards). Beware of edits." Changes to time or venue may not reach pokedata. This matters for the sync's change detection (M10).
- The website's "Last Update" is shown in GMT+1, one hour ahead of Lisbon in winter. Whether `when` is local venue time is checked in the field mapping.

## Requests made

All on 18 Nov 2026, one by one with `curl`, User-Agent `Pre-Flight spike`, approved by Bruno.

| # | Request | Result | Saved as |
| --- | --- | --- | --- |
| 1 | `GET /events/api/_tcg/_country/PT` | 200, 850 events | `pt-tcg-upcoming-2026-11-18.json` |
| 2 | `GET /events/api/_country/PT` | 200, identical to 1 | `pt-all-games-2026-11-18.json` |
| 3 | `GET /events/api/_tcg/cups/challenges/pre/_country/PT` | 200, 39 events | `pt-tcg-cups-challenges-pre-2026-11-18.json` |
| 4 | `GET /events/api/_tcg/_country/PT/_start/2020-01-01/_end/2020-01-02` | 200, `[]` | `pt-tcg-empty-range.json` |
| 5 | 1 again | identical | not saved |
| 6 | `GET /events/api/_country/PT/_tcg` | identical to 1 | not saved |
| 7 | `GET /events/api/_tcg/ftcg/_country/PT` | 200, `[]` | not saved |
| 8 | website `POST`, Friendly TCG only | 100 friendlies | not saved |
| 9 | website `POST`, all TCG (body above) | 100 events, page 0 | not saved |

No error response was produced: invalid filters return `200` with `[]` instead of an error.

## Still to do (Slice 2)

- Field-by-field mapping to `ImportedEvent`: times and zones (incl. Madeira and Azores), end times, venue fields.
- Questions for the pokedata.ovh author.
