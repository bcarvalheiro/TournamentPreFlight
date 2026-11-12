# M10 · Nightly import and sync

**Branch**: `m10-import-sync` · **Read**: `architecture.md` (Event import, Time, Data model: events, venues, import_runs), `docs/pokedata.md`, `product.md` (Notifications: event changed, possibly cancelled)

## Goal
Real Portuguese events arrive every night, safely.

## Scope
- `EventSource` interface, `ImportedEvent`, `PokedataSource` (mapping from `docs/pokedata.md`, tested on the M03 fixtures).
- Venue matching and creation by normalised name + city.
- `EventSync` service and `events:sync {--source=pokedata} {--dry-run}` command, implementing every step in `architecture.md`, writing `import_runs`.
- Abort rules: exception, empty result, result under half the previous good run. Admins are notified (mail) on abort.
- On significant change: update, set `changed_at`, clear confirmations, dispatch a `StillGoingNotification` job with reason "changed" (the mail itself can be a simple stub until M12).
- Possibly-cancelled flagging and clearing; dispatch `PossiblyCancelled` (stub until M12).
- Scheduler entry nightly at 03:30 Europe/Lisbon, `withoutOverlapping`, `onOneServer`.
- Admin page listing import runs (simple table).

## Acceptance criteria
1. Empty DB + fixture: every event created once; running again creates and updates nothing.
2. Fixture variant with a changed start time: event updated, its going answers unconfirmed, notification job dispatched once per affected account. A changed title updates silently.
3. Fixture variant missing an event: it is flagged possibly cancelled with its answers kept; when it reappears the flag clears.
4. HTTP failure, empty response, and a response under half the last run each abort with no changes, an `aborted` run row, and an admin mail.
5. Matching order works: same Guid from a player-submitted event merges instead of duplicating.
6. Local times convert to UTC correctly, including a DST boundary date.
7. `--dry-run` reports counts and writes nothing.
8. No real HTTP in tests (`Http::fake` with fixtures).
