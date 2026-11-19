# M04 · Domain schema and seed data

**Branch**: `m04-domain-schema` · **Read**: `architecture.md` (Data model, Code layout, Time), `product.md` (Accounts and profiles, Events)

## Goal
All v1 tables, models, enums and factories, and realistic seed data to build the UI on.

## Scope
- Migrations for: social_accounts, magic_login_tokens, player_profiles, shops, events, attendances, event_follows, event_reports, import_runs; `is_admin` and `notification_settings` on users. (Password removal waits for M06.)
- Enums: EventType, EventStatus, EventSourceKey.
- Models with casts, relationships, and scopes: `Event::upcoming()` (listed, ends_at > now, ordered by starts_at), `Event::ofTypes()`, `Event::atShops()`, `Event::between()`.
- Default end-time estimate by type as `EventType::estimatedHours()` (Casual 2h, Prerelease 3h, Challenge 4h, Cup 6h); the `Event` model's `saving` hook writes `ends_at` from it.
- Factories for every model. A `DemoSeeder` mirroring the prototype: the nine invented shops and cities from `design/prototype/app.js`, ~30 events over the next 6 weeks (one ongoing, one ended), attendances giving realistic counts, two demo accounts (solo, and a parent with two child profiles), one admin.

## Out of scope
Controllers, pages, auth changes.

## Acceptance criteria
1. Migrations run up and down cleanly.
2. Unique keys enforced: (event_id, player_profile_id), player_id, official_guid, official_url, (provider, provider_user_id), shops.official_name_key, event_follows (event_id, user_id). One test per key.
3. `Event::upcoming()` includes an ongoing event, excludes an ended one, excludes pending/hidden/cancelled, orders by start.
4. `php artisan migrate:fresh --seed` produces the demo data; a test asserts the counts per table.
5. Saving a profile under 13 this year sets `is_child`; saving it without consent fails validation at the model/action level.
