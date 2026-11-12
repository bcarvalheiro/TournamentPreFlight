# M03 · pokedata.ovh spike

**Branch**: `m03-pokedata-spike` · **Read**: `architecture.md` (Event import, Time)

## Goal
Know exactly what pokedata.ovh returns for Portugal before building the importer. This is research, not product code.

## Scope
- Read https://pokedata.ovh/events/api/help. Find the request that returns upcoming Portuguese events (country filter), TCG only, all tournament types. Note how non-TCG events are marked so the importer can drop them.
- With Bruno's OK, make a small number of real requests (no loops, no load). Save 2–3 raw responses to `tests/Fixtures/pokedata/` (for example `pt-upcoming-2026-11-xx.json`), plus one deliberately empty or error response if you can produce one safely.
- Write `docs/pokedata.md`: request URL and parameters, every response field with example values and our mapping to `ImportedEvent`, how type names appear (and our mapping to friendly/league/challenge/cup/other), whether times are local and in which zone, whether end times exist, whether the official Guid and locator URL are present, venue fields, how Madeira/Azores events look, pagination, anything odd.
- List questions for the pokedata.ovh author (refresh behaviour, rate limits, terms) at the end of `docs/pokedata.md` so Bruno can send them.
- Update `architecture.md` Event import section if the facts differ from the assumptions there.

## Out of scope
The importer itself (M10). No scheduled jobs.

## Acceptance criteria
1. Fixtures exist and are valid JSON.
2. `docs/pokedata.md` maps every field the importer needs, or says plainly it is missing and what we do instead.
3. `decisions.md` open question about pokedata fields is ticked or updated.

## Notes
If the network is blocked or the API is down, stop and tell Bruno; do not invent a response shape.
