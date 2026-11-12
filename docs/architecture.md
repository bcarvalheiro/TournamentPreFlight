# Architecture

## Stack

| Layer | Choice | Notes |
| --- | --- | --- |
| Framework | Laravel 13, PHP 8.4 | Laravel 14 expected Q1 2027 and needs PHP 8.4, so no PHP upgrade then |
| Database | MySQL 8.4 LTS | Tests run on MySQL too (a `preflight_test` database), not SQLite, so JSON and date behaviour match production |
| Frontend | Official React starter kit: Inertia, React 19, TypeScript, Tailwind 4, shadcn/ui | Check the installed Inertia version on day one and record it in `decisions.md` |
| Routes in TS | Whatever the starter kit ships (Wayfinder or Ziggy) | Don't add a second one |
| Auth | Starter kit auth (Fortify) for sessions; Socialite for Google; Socialite Providers for Apple and Discord; own magic-link flow | Password login and registration are removed |
| Background | Scheduler + queues on the database driver | One `queue:work` process in production |
| Mail | Laravel Mail, Markdown mailables; Mailpit locally | Provider is an open decision |
| Tests | Pest 4 (+ browser plugin), Larastan, Pint | See `workflow.md` |
| Dev help | Laravel Boost (MCP server with version-specific docs, schema, tinker) | Optional but recommended |

## Code layout

Follow Laravel defaults. Additions:

```
app/
  Actions/            single-purpose classes: SetAttendance, CreatePlayerProfile, ApproveSubmission
  Data/Public/        the ONLY shapes sent to other players: EventCardData, EventPageData, EventCountsData
  Data/Own/           shapes for the signed-in user's own data: OwnProfileData, OwnAttendanceData
  Domain/Divisions/   DivisionResolver (birth year + event date -> Division)
  Enums/              EventType, EventStatus, AttendanceStatus, Division, EventSourceKey
  Import/             EventSource interface, PokedataSource, EventSync, SyncReport, ImportedEvent
  Notifications/      StillGoingNotification, ThresholdReached, PossiblyCancelled, SubmissionReviewed
  Http/Controllers/Public, /Account, /Admin
resources/js/
  pages/              Inertia pages: home, events/show, profiles/*, admin/*
  components/pf/      our components: PairingsSheet, MatchSlip, TypeBall, GoingButton, ProfilePicker, FilterBar, FilterSheet
  lib/time.ts         all date formatting, Europe/Lisbon, en-GB style
tests/
  Feature/  Unit/  Browser/  Arch/  Fixtures/pokedata/
```

## Data model

All timestamps UTC. `id` bigint, `timestamps()` on every table unless noted.

**users** (a login): name, email (unique), email_verified_at, is_admin (bool, default false), notification settings (json: reminders, changes, thresholds, moderation; all true by default), remember_token. No password column (drop it from the starter kit migration in M06).

**social_accounts**: user_id, provider (google, apple, discord), provider_user_id, provider_email (nullable). Unique (provider, provider_user_id).

**magic_login_tokens**: email, token_hash, expires_at, used_at (nullable), ip. A link is valid once, for 15 minutes.

**player_profiles**: user_id (owner), name, player_id (string of digits, unique), birth_year (smallint), is_child (bool, computed on save: under 13 this calendar year), consent_given_at (nullable, required when is_child), consent_by_user_id (nullable). Soft deletes: no; deletion cascades to attendances.

**seasons**: name ("2027"), starts_on, ends_on (dates), junior_born_from (e.g. 2015), senior_born_from (e.g. 2011). Masters is everything older. Seeded; edited by admin.

**venues** (a host shop; not a shop account): name, address (nullable), city, district (nullable), official_name_key (normalized name + city, for matching imported events). Unique (official_name_key).

**events**: venue_id, source (pokedata, player), source_event_id (nullable), official_guid (nullable, unique), official_url (nullable, unique), display_id (official tournament ID, nullable), type (friendly, league, challenge, cup, other), title, starts_at, ends_at, ends_at_estimated (bool), status (pending, listed, hidden, cancelled), possibly_cancelled_at (nullable), last_seen_at (nullable, set by sync), changed_at (nullable, last significant change), added_by_user_id (nullable), moderation_note (nullable). Unique (source, source_event_id). Index (status, ends_at, starts_at).

**attendances**: event_id, player_profile_id, status (going, maybe), confirmed_at (nullable), anonymised_at (nullable). Unique (event_id, player_profile_id). Removing an answer deletes the row. After anonymisation player_profile_id becomes null and the unique key no longer applies (MySQL allows several NULLs). Index (event_id, status).

**event_follows**: event_id, user_id, notify_at_count, notify_on_drop (bool), notified_at (nullable). Unique (event_id, user_id).

**event_reports**: event_id, user_id, kind (changed, cancelled, wrong), message. For "Report a change".

**import_runs**: source, started_at, finished_at, status (ok, failed, aborted), created, updated, flagged, unchanged (ints), error (text, nullable), payload_sample (json, nullable).

Plus the framework tables: sessions, jobs, failed_jobs, cache, notifications (not needed unless database notifications are used).

### Counts

Computed with `withCount` filtered by status and joined to profiles for divisions. No denormalised counter in v1. If the home page query gets slow, add a cached counts table later, written by the `SetAttendance` action. Divisions are computed in SQL from `birth_year` and the event's season bounds, or in PHP for one event's page.

## Privacy enforcement (rule 1 in CLAUDE.md)

- Public controllers return only `app/Data/Public` objects. These classes have no name, Player ID or birth-year fields by construction.
- An Arch test forbids `App\Models` in public controller return values and Inertia props.
- `tests/Feature/Privacy/NoPersonalDataLeaksTest.php` seeds profiles named like `Zzleak Name 4711` with Player ID `9988776655`, visits every route in a dataset as guest and as another user, and asserts neither string appears in the HTML or the Inertia JSON. Each new public route is added to the dataset in the same PR.
- Logs never contain profile names or Player IDs.

## Time

`APP_TIMEZONE=UTC`. Database in UTC. Imported local times are parsed as `Europe/Lisbon` then converted (note Madeira and Azores: if pokedata gives a venue in those regions, use `Atlantic/Madeira` / `Atlantic/Azores`; record this in the spike). The frontend formats every date through `resources/js/lib/time.ts` with `timeZone: 'Europe/Lisbon'` and `en-GB` style ("Sat 15 Nov, 10:00"). Date filter presets are computed in Lisbon time on the server.

## Event import

```php
interface EventSource
{
    public function key(): EventSourceKey;           // enum, e.g. EventSourceKey::Pokedata
    /** @return iterable<ImportedEvent> */
    public function upcoming(): iterable;            // throws SourceUnavailable on failure
}
```

`ImportedEvent` is a plain readonly object: sourceEventId, officialGuid, officialUrl, displayId, type, title, startsAt (UTC CarbonImmutable), endsAt (nullable), venueName, venueAddress, city.

`PokedataSource` maps the real response (fields recorded in the M03 spike in `docs/pokedata.md`) to `ImportedEvent`. Unknown types map to `other`. It only asks for Portugal and only keeps TCG events.

`EventSync` (`php artisan events:sync {--source=pokedata} {--dry-run}`), scheduled nightly at 03:30 Lisbon:

1. Fetch. On exception, an empty result, or a result less than half the size of the last good run, abort: change nothing, record the run as aborted, notify admins.
2. For each imported event, find an existing one by (source, source_event_id), then official_guid, then official_url. Not found: create as `listed`. Found and unchanged: touch `last_seen_at`. Found and changed in starts_at, ends_at or venue: update, set `changed_at`, clear `confirmed_at` on its going answers, dispatch `StillGoingNotification` (reason: changed) to the accounts concerned. Other field changes (title, display ID) update silently.
3. Each listed, upcoming event from this source not seen tonight gets `possibly_cancelled_at` (once) and a `PossiblyCancelled` notification. If it reappears later, the flag is cleared.
4. Write the `import_runs` row with the counts.

Safe to run twice: a second run finds everything unchanged. The first fill of an empty database is just the first run.

Player-submitted events go through the same matching (Guid, then URL) before they are saved.

## Notifications

Laravel Notifications on the `mail` channel, queued. Signed URLs (`URL::temporarySignedRoute`, valid until the event ends) for Yes/No answers in emails so no login is needed. Schedules (Lisbon time): `reminders:day-before` at 18:00 for events starting the next day; `thresholds:check` runs after every `SetAttendance` (dispatched job), not on a schedule; `attendances:anonymise` daily. Every notification respects the user's settings and includes a signed one-click unsubscribe per category.

## Auth flows

- Magic link: enter email, receive a link, click it; it signs in and creates the account if new. Rate limit 5 per hour per email and per IP.
- Social: Socialite redirect and callback. Match by (provider, provider_user_id); if none, match an existing user by verified email and link; else create. Apple sends name only on first login; store it then.
- After first sign-in with no profiles, go to "Create your player profile" before anything else, keeping the pending intent.
- **Pending intent**: when a guest presses "I'm going", store `{event_id, return_to}` in the session, send them to sign in, and after sign-in redirect to `return_to?intent=going&event={id}`; the page opens the picker (several profiles) or applies it (one profile).

## Admin

Simple Inertia pages behind `can:admin` (Gate on `is_admin`): pending submissions (approve, edit, reject with note), events (search, hide, unhide, mark cancelled), reports, import runs, seasons. No admin package.

## Environments and CI

- Local: Laravel Herd or Sail, MySQL 8.4, Mailpit.
- CI: GitHub Actions with a MySQL 8.4 service runs `composer verify` on every push and PR.
- Production: hosting is an open decision. Needs PHP 8.4, MySQL 8.4, a cron for the scheduler, one queue worker, HTTPS.

## Security basics

CSRF on (Inertia default), rate limits on login, answer and submission routes, policies on every Account and Admin controller (`$this->authorize`), signed routes for email actions, `Http::preventStrayRequests()` in tests, secrets only in `.env`.
