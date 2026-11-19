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
| `nonpremier TCG` | (field absent) | casual (League and Friendly) |
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

Counts kept on 18 Nov 2026: 777 casual, 29 challenge, 10 cup (816 of 850).

### Why not the narrowed request

`_tcg/cups/challenges/pre/_country/PT` does filter by product: it returned exactly the 39 cups and challenges with `product: tcg`. But the API has no sub-option for friendlies, and `_tcg/ftcg/_country/PT` (the website's name for friendlies) returns `[]`: an unknown sub-option matches nothing. So friendlies come only from the bare `_tcg`, which needs the filter above.

## Tournament types

| pokedata `type` | Website label | Our type |
| --- | --- | --- |
| `nonpremier TCG` | TCG Friendly | casual |
| `League Challenge` (`product: tcg`) | TCG League Challenge | challenge |
| `League Cup` (`product: tcg`) | TCG League Cup | cup |
| not seen yet | Pre Release | prerelease |

- **Prerelease** is a type of its own (Bruno, 18 Nov 2026). None were listed on 18 Nov because prereleases only run before a new set is released; the website showed 0. Its `type` and `product` values are unknown until one appears: check before relying on the mapping.
- **Casual**: pokedata uses `nonpremier TCG` for both League nights and Friendlies (Bruno, 18 Nov 2026). Some carry League names ("Liga semanal - Terça Feira", "Liga Pokémon TCG Semanal Competitive"), most have no name. They map to one **casual** type: League and Friendly are collapsed (Bruno, 18 Nov 2026), since they mean the same to most players.
- **Known risk**: a bigger tournament can be sanctioned as `nonpremier TCG`, and we would list it as casual. Needs more digging (later).
- The website also has `premier` and `mss` filters (see below). Neither appeared in the Portugal data; values unknown.

## Event-specific URL and tournament ID

`pokemon_url` is either:

- event-specific, ending in the official tournament ID: `https://www.pokemon.com/us/pokemon-trainer-club/play-pokemon-tournaments/26-11-001729/`. All 51 cups and challenges (39 of them TCG) and 53 of the 777 casual events on 18 Nov 2026: 104 in all, 92 among the 816 kept;
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
- The website warns: "Events are retrieved once (but some data is updated afterwards). Beware of edits." Changes to time or shop may not reach pokedata. This matters for the sync's change detection (M10).
- The website's "Last Update" is shown in GMT+1, one hour ahead of Lisbon in winter. Whether `when` is the shop's local time is checked in the field mapping.

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

## Field mapping

`ImportedEvent` (the importer's plain object, M10) and where each property comes from. One property per fact: no duplicates.

| `ImportedEvent` | Source field | Example | Rule |
| --- | --- | --- | --- |
| `guid` | `guid` | `0aaf9ad5-8100-42c8-ae2c-98ab878999be` | Always present, unique (850 of 850). The one identifier for matching; the website uses the same value |
| `type` | `type` + `product` | `League Cup` + `tcg` | Filter contract above. Values: casual, challenge, cup, prerelease |
| `title` | `name` | `Liga Casual Pokémon` | `null` when `name` is empty (723 of 816 kept events). The app then shows the type and the shop name as the title (Bruno, 18 Nov 2026), so a name added later in pokedata still takes over |
| `startsAt` | `when` | `2026-11-21 15:00:00` | Local time without a zone; parsed as `Europe/Lisbon`, stored as UTC (CarbonImmutable). See Time |
| `officialUrl` | `pokemon_url` | `…/play-pokemon-tournaments/26-11-001729/` | Only when event-specific (92 of the 816 kept events; 104 of all 850); the generic locator URL gives `null` |
| `displayId` | end of `pokemon_url` | `26-11-001729` | The official tournament ID; `null` when the URL is generic |
| `shopName` | `shop` | `VERSUS GAMECENTER LISBOA` | Spelling varies, see Shop |
| `shopAddress` | `street_address` | `ESTR. DE SÃO LUÍS 70, 8000-123 FARO, PORTUGAL` | Free text, format varies |
| `city` | `city` | `Faro` | Without accents (`Portimao`, `Loule`) |
| `leagueId` | `league` | `26062297` | The organiser's league ID; stable where shop names vary. Kept for shop matching, decided later (M04/M10) |

**Missing, and what we do instead**

- **End time**: pokedata has none. `ImportedEvent` has no `endsAt`; the app estimates the end by type (`product.md`).
- **Postal code**: `postal_code` is always empty. Some addresses contain one; we do not parse it.

## Every field

All values are strings, including coordinates and counts. 850 events on 18 Nov 2026.

| Field | Filled | Example | Used |
| --- | --- | --- | --- |
| `type` | 850 | `nonpremier TCG` | yes, filter and type |
| `product` | 51 (cups and challenges only) | `tcg` | yes, filter |
| `name` | 105 | `Pokémon League Challenge - Algés` | yes, title |
| `date` | 850 | `2026-11-21` | no, `when` has it |
| `time` | 850 | `15:00:00` | no, `when` has it |
| `when` | 850 | `2026-11-21 15:00:00` | yes, startsAt. Always `date` + `time` |
| `guid` | 850 | `0aaf9ad5-…` | yes |
| `pokemon_url` | 850 (104 event-specific) | `…/26-11-001729/` | yes, officialUrl and displayId |
| `shop` | 850 | `DECKOUT` | yes |
| `street_address` | 850 | `R. JOÃO PIRES PINTO 39 1º ANDAR, 8135, PORTUGAL` | yes |
| `city` | 850 | `Almancil` | yes |
| `state` | 850 | `Faro` | no. District; not standardised |
| `country_code` | 850 | `PT` | no, always `PT` (we ask for it) |
| `latitude`, `longitude` | 850 | `37.0746`, `-7.97926` | no (see Time) |
| `league` | 850 | `26062297` | yes, leagueId |
| `date_added` | 850 | `2026-08-23` | no. When pokedata first saw the event |
| `cost` | 91 | `5€`, `6.5`, `7 euros`, `6,5`, `€10` | no. Free text, not used |
| `totalPlayers`, `TCaccounts`, `juniors`, `seniors`, `masters` | always `"0"` | `0` | no |
| `postal_code`, `status`, `category`, `tournament_date`, `tournament_completed`, `Event_website`, `Third_party_registration_website` | always empty | | no |

## Time

- `when` is the start time in the shop's local time, with no zone or offset. The values are what a shop would announce (15:00 for 474 events, 10:30, 19:00, 21:00) and match the times on the website.
- The importer parses `when` as `Europe/Lisbon` and stores UTC.
- **Madeira and Azores: be careful.** On 18 Nov 2026 there were no events on the islands (every `state` was a mainland district). Madeira is on Lisbon time, but the Azores are one hour behind, so an Azores event parsed as Lisbon time would be an hour off. When island events start to appear, check how they look (`state`, `city`, coordinates) and handle the Azores zone then (Bruno, 18 Nov 2026: no special handling until then).
- No end time (see Field mapping).

## Shop

- One `league` ID can appear with two spellings of the shop. All 8 cases on 18 Nov 2026:

  | `league` | Spellings |
  | --- | --- |
  | `26071312` | `ABISSAL GAMES` / `ABYSSAL GAMES` |
  | `6237897` | `VERSUS GAMECENTER` / `VERSUS GAMECENTER LISBOA` |
  | `26060255` | `TBMJ` / `TBMJ COLLECTIBLES` |
  | `44904` | `ARENA PORTO` / `LOJAS ARENA PORTO` |
  | `6241551` | `PLAY YOUTH STORE` / `PLAY YOUTH A LOJA` |
  | `25092901` | `TEMPLARS ARENA LISBOA` / `TEMPLARS ARENA GAMECENTER (LISBOA)` |
  | `25127247` | `DIVERCENTRO` / `DIVERCENTRO LTA.` |
  | `6236586` | `GG LOUNGE` / `GGLOUNGE` |

- 8 shops appear with two addresses or two sets of coordinates.
- So shop name and city alone are not a reliable key. `leagueId` is kept as a candidate; the choice is for M04/M10.

## Questions for the pokedata.ovh author

1. Is this use fine with you: a free, non-commercial fan site for Portugal that requests `/events/api/_tcg/_country/PT` once a night and credits pokedata.ovh?
2. Is there a rate limit or a preferred time of day for requests?
3. How often is the data refreshed from the official locator, and are changes to time, shop or cancellations picked up after the first retrieval ("Events are retrieved once")?
4. A bare `_tcg` returns VG and GO events too. Is that intended? Could the API accept a friendly option like the website's `ftcg` (for example `_tcg/cups/challenges/pre/ftcg`)?
5. What `type` and `product` values do prereleases, and the website's `premier` and `mss` filters, have?
6. Is `when` always the shop's local time? What happens for events in the Azores (UTC−1)?
7. Is `guid` the official Event Locator GUID, and does it stay the same when an event is edited?
8. When an event is cancelled or removed from the locator, does it disappear from the API or stay with a status? (`status` is always empty today.)
