# M04 · Domain schema and seed data

**Branch**: `m04-domain-schema` · **Read**: `architecture.md` (Data model, Code layout, Time), `product.md` (Accounts and profiles, Events)

## Goal
All v1 tables, models, enums and factories, a working DivisionResolver, and realistic seed data to build the UI on.

## Scope
- Migrations for: social_accounts, magic_login_tokens, player_profiles, seasons, venues, events, attendances, event_follows, event_reports, import_runs; `is_admin` and `notification_settings` on users. (Password removal waits for M06.)
- Enums: EventType, EventStatus, AttendanceStatus, Division, EventSourceKey.
- Models with casts, relationships, and scopes: `Event::upcoming()` (listed, ends_at > now, ordered by starts_at), `Event::ofTypes()`, `Event::atVenues()`, `Event::between()`.
- `DivisionResolver::for(int $birthYear, CarbonInterface $eventDate): Division` using the season that contains the event date; throws a clear exception if no season covers it.
- Default end-time estimate by type in `config/preflight.php` (Cup 9h, Challenge 5h, League 3h, Friendly 3h, other 4h).
- Factories for every model. A `DemoSeeder` mirroring the prototype: the nine invented shops and cities from `design/prototype/app.js`, ~30 events over the next 6 weeks (one ongoing, one ended), attendances giving realistic counts, two demo accounts (solo and family with a Junior and a Senior child), one admin, the 2027 season.

## Out of scope
Controllers, pages, auth changes.

## Acceptance criteria
1. Migrations run up and down cleanly.
2. Unique keys enforced: (event_id, player_profile_id), player_id, (source, source_event_id), official_guid, official_url, (provider, provider_user_id). One test per key.
3. DivisionResolver: born 2015 is Junior, 2014 Senior, 2011 Senior, 2010 Masters for a 2027-season event date; an event date outside every season throws.
4. `Event::upcoming()` includes an ongoing event, excludes an ended one, excludes pending/hidden/cancelled, orders by start.
5. `php artisan migrate:fresh --seed` produces the demo data; a test asserts the counts per table.
6. Saving a profile under 13 this year sets `is_child`; saving it without consent fails validation at the model/action level.
