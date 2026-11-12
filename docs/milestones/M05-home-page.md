# M05 · Home page on seeded data

**Branch**: `m05-home-page` · **Read**: `product.md` (Home page), `design.md` (Home page, Type icons), `architecture.md` (Privacy enforcement, Time), prototype

## Goal
The real home page, matching the Pairings design, on seeded data. Quick buttons are visible but only route to sign-in for now.

## Scope
- `HomeController` (public) returning `EventCardData` objects only: id, url, display_id, type, venue name, city, starts_at, ends_at, is_ongoing, going_count, possibly_cancelled. Plus `filters` (current values) and `shops` (venues with upcoming events and counts) and `totals` (matched, all).
- Query: upcoming events, filters from the query string (`type[]`, `shop[]`, `when=any|week|weekend|7|30|custom`, `from`, `to`), date presets computed in Lisbon time, cap 25 after filters. `upNext`: first 5 upcoming ignoring filters.
- Page components: hero with coin flip, `PairingsSheet`, `FilterBar` (sticky), `FilterSheet`, agenda grouped by Lisbon day, `MatchSlip`, legend, disclaimer.
- Filter changes update the URL (Inertia visit with `preserveScroll`, `only` the list props).
- Create `tests/Feature/Privacy/NoPersonalDataLeaksTest.php` with the home page as its first route, and the Arch rule for public controllers.

## Out of scope
Working "I'm going" (M08), sign-in (M06), event page (M09: slips link to `/events/{id}` which can 404 for now, or use a placeholder route).

## Acceptance criteria
1. Lists only upcoming and ongoing listed events, max 25, ordered by start; an ended event never appears.
2. Up next shows exactly the first 5 upcoming, unaffected by filters.
3. Each filter works alone and combined (type AND shop AND date); multi-select within a group is OR. Date presets "This week", "This weekend", "Next 7", "Next 30", custom From/To are correct around a Sunday and across month ends (freeze time in tests).
4. Date filter applies before the cap: with 40 matching events the page says "First 25 of 40".
5. Counts are going only (maybe excluded).
6. Shop filter lists only venues with upcoming events, with counts.
7. Privacy test passes: no seeded names or Player IDs in the response.
8. Browser test at 390px: filter sheet opens, choosing Cup and a shop updates the list and the URL; reloading the URL keeps the filters.
9. Signed out, the quick button sends to the sign-in route (placeholder page is fine).

## Notes
Run `/ui-check /` and compare side by side with `design/prototype/pairings.html`.
