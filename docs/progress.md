# Progress

Claude updates this file at the end of every session (`/wrap-up`). Read it at the start of every session.

## Current

- **Milestone**: M06 · Sign in: email and password, Google, Discord, X (in progress).
- **Branch**: m06-auth
- **State**: All slices done. Next: `/rethink-tests`, then `/review`.

### Plan (M06)

- [x] Slice 1a: remove two-factor auth, passkeys and magic-login tokens: Fortify features, `User` traits, kit pages and components, tests; migration drops the 2FA columns, `passkeys` and `magic_login_tokens` (`config/fortify.php`, `FortifyServiceProvider`, `User.php`, `MagicLoginToken.php`, migration, `routes/settings.php`, `pages/auth/two-factor-challenge.tsx`, `pages/settings/security.tsx`, kit components, `architecture.md`; `NoTwoFactorOrPasskeysTest`) · AC4
- [x] Slice 1b: remove the dashboard and Settings → Appearance; Fortify `home` → `/` (`routes/web.php`, `routes/settings.php`, `config/fortify.php`, `pages/dashboard.tsx`, `pages/settings/appearance.tsx`, kit nav; `AuthenticationTest` updated, `DashboardTest` removed) · groundwork
- [x] Slice 2: "I am 13 or older" box on registration; migration adds `users.age_confirmed_at`; no box, no account (migration, `CreateNewUser`, `User.php`, `UserFactory`, `pages/auth/register.tsx`, `architecture.md`; `RegistrationTest`) · AC1
- [x] Slice 3: player gate `EnsurePlayerReady` (verified email, password, player profile) on every page except auth, settings and logout; placeholder `profiles.create`; migration makes `users.password` nullable (`app/Http/Middleware/EnsurePlayerReady.php`, `bootstrap/app.php`, `routes/web.php`, `pages/profiles/create.tsx`, migration, `architecture.md`; `PlayerGateTest`, kit rate-limit and logout tests kept green) · AC2, AC6
- [x] Slice 4: pending intent: guest "I'm going" stores `{event, return_to}` in the session (same-site paths only), sets the intended URL, goes to `/login`; after sign-in and the gate the page gets `pendingIntent` once (`routes/web.php`, `Auth\PendingIntentController`, `User::isPlayerReady()`, `EnsurePlayerReady`, `HandleInertiaRequests`, `pf/going-button.tsx` + callers, `types/global.d.ts`; `PendingIntentTest`, `event-list-item.test.tsx`, `pairings-sheet.test.tsx`) · AC5
  - Changed (21 Nov): `NoPersonalDataLeaksTest` not extended: the new route only redirects and renders no page, and `pendingIntent` holds only an event id; `User::isPlayerReady()` added so the gate and the prop share one check.
- [x] Slice 5: social sign-in for existing accounts: Socialite + Socialite Providers Discord, `SocialProvider` enum (google, discord, x; `confirmsEmail()`), generic `/auth/{provider}/redirect|callback` with `whereIn`, `Actions\SignInWithSocialAccount`; known identity signs in, provider-verified email links (an unverified local account is verified and its password cleared), unverified provider email refused politely, cancelled sign-in back to login (`composer.json`, `config/services.php`, `.env.example`, `bootstrap/providers.php`, `AppServiceProvider`, `app/Enums/SocialProvider.php`, `SocialAccount.php`, `Auth\SocialAuthController`, `routes/web.php`, `architecture.md` Code layout, `decisions.md`; `SocialSignInTest`) · AC3
  - Changed (Bruno, 21 Nov): offered as 5a/5b, kept as one slice; the cancel case and clearing the password of an unverified account added.
- [x] Slice 6a: a new social identity is kept in the session (`social_signup`) and sent to `/welcome`, which shows the name prefilled and the email (fixed when the provider certified for it) (`Auth\SocialAuthController`, `Auth\WelcomeController@show`, `pages/auth/welcome.tsx`, `routes/web.php`; `SocialSignUpTest`) · AC3
- [x] Slice 6b: submitting `/welcome` with the age box creates the account (no password; email verified only when the provider certified, otherwise the verification email), links the identity, signs in and returns to the intended URL (`Actions\CreateUserFromSocialIdentity`, `WelcomeController@store`, `routes/web.php`; `SocialSignUpTest`) · AC3. Changed in review: only a provider-vouched email signs up, so the account always starts verified; "AC7" corrected (M06 has six)
  - Changed (Bruno, 21 Nov): Slice 6 split in two (~300 lines together); account creation is its own Action, `CreateUserFromSocialIdentity`, so it can be tested on its own.
- [x] Slice 7a: auth pages (sign in, register, forgot/reset, verify, confirm, welcome) in the Pre-Flight look: band, card, footer with the notice; "Sign in" copy (`layouts/auth/auth-simple-layout.tsx`, `pages/auth/login.tsx`, `pages/auth/register.tsx`; `auth-simple-layout.test.tsx`) · design, rule 7
- [x] Slice 7b: "Continue with Google / Discord / X" buttons on sign in and register, only for configured providers; icons from each provider's brand kit (`SocialProvider::enabled()`, `FortifyServiceProvider`, `pf/social-buttons.tsx`, `pages/auth/login.tsx`, `pages/auth/register.tsx`, `decisions.md`; `SocialButtonsTest`, `social-buttons.test.tsx`) · AC3 UI
  - Changed (Bruno, 21 Nov): Slice 7 split in two (~250 lines together).

## Milestones

| # | Milestone | Status | Notes |
| --- | --- | --- | --- |
| M01 | Scaffold, tooling and CI | done | PR #1 and #2 merged |
| M02 | Design tokens and layout shell | done | PR #3 merged. Signed-in `/ui-check` after M04 |
| M03 | pokedata.ovh spike | done | PR #5 merged |
| M04 | Domain schema and seed data | done | PR #6 merged |
| M05 | Home page on seeded data | done | PR #7 merged |
| M06 | Sign in: email and password, Google, Discord, X | in progress | |
| M07 | Player profiles | todo | |
| M08 | "I'm going" and Maybe | todo | |
| M09 | Event page | todo | |
| M10 | Nightly import and sync | todo | |
| M11 | Submitted events and admin | todo | |
| M12 | Email notifications | todo | |
| M13 | Apple login | todo | Discord moved to M06 |
| M14 | Privacy, legal and account deletion | todo | |
| M15 | Launch hardening | todo | |

Status values: todo, in progress, review, done.

## Session log

<!-- Newest first. One entry per session: date, milestone, what was done, what is left, anything surprising. -->

### 2026-11-20/21 · M05 · Home page on seeded data

**Outcome.** All nine acceptance criteria met and tested. The public home page `/` has:
- a hero with the coin flip, and the "Up next" pairings sheet;
- a sticky filter bar with removable chips and counts;
- the filter sheet (dates, types, shops), applied as you tick;
- a day-by-day agenda of event list items, with the type legend, the disclaimer and the short footer notice.

Server side: `Public\HomeController` sends `EventCardData` and `ShopFilterData` DTOs, and `HomeFilterRequest` reads type, shop, date presets and picked dates in Lisbon days, dropping anything it can't use. `composer verify` green: Pint, Larastan 0 errors, build, `tsc`, `vp check`, 64 Vitest, 162 Pest (8,530 assertions, browser tests included). Run in 15 planned slices (7 and 12 split, 13 and 14 kept whole on Bruno's call, 15 added from `/ui-check`), each proposed, built test-first and committed by Bruno.

**Commits** (`main..HEAD`)
- Setup:
  - `bb16787` plan
  - `607d4e3` fewer trademark names (hero "Pre-Flight", short notice)
- Groundwork:
  - `fc8f2d2` components into `components/pf/` (Slice 1)
  - `8ad81cd` casual/prerelease types (Slice 2)
- Server:
  - `bc10086` home controller and card DTO (Slice 3)
  - `e9a2c9b` privacy test and Arch rules (Slice 4)
  - `c29fbce` up next and totals (Slice 5)
  - `5e6d218` type and shop filters (Slice 6)
  - `d56cfa2` date presets (7a)
  - `8488334` picked dates (7b)
  - `73e7a0a` shop list with counts (Slice 8)
- Band:
  - `1c8a168` hero and coin flip (Slice 9)
  - `0cb7ba2` pairings sheet, with readable band classes (Slice 10)
- List:
  - `f36afae` `EventListItem` (Slice 11)
  - `9bdf077` agenda (12a)
  - `8b7fd24` legend, disclaimer, footer notice (12b)
- Filters:
  - `5b429d0` sticky filter bar (Slice 13)
  - `ed2e37f` filter sheet (Slice 14)
- `28f47bf` UI check fixes (Slice 15)
- `/rethink-tests`:
  - `5c07831` explicit test days
  - `8d79938` chip removal and Clear all in the browser test
  - `cd2d3b6` case-blind privacy check
- `/review`:
  - `54958ac` nested, repeated and stale filter values dropped
  - `64f4bb3` tie-breaker by id
  - `9b5d616` filter sheet draft and typed dates
  - `bab21fe` picked dates only with "Pick dates"
  - `e0591da`, `20d8b2b`, `a975d91`, `0faa55d` accessibility touch-ups
  - `f63c859` privacy test with a child and a birth year
  - `ca9dc1a` notes for later
- `c23d972` git deny rules in `.claude/settings.json` (outside the milestone, after I staged files)

**Decisions by Bruno** (all in `decisions.md`)
- **Structure:** DTOs in `app/Data/Public/` for public data; camelCase keys; our components in `components/pf/`.
- **Filters:**
  - apply as you tick;
  - invalid values are dropped, never rejected;
  - shop counts follow type and date, not the shop filter;
  - date presets as in product.md.
- **Trademarks:** names as few as possible.
  - hero "Pre-Flight", with one "Pokémon TCG" mention;
  - footer "Independent fan project. Not affiliated with, endorsed or sponsored by Nintendo or The Pokémon Company."
- **The list item:** it is `EventListItem` (a row of the upcoming list). It shows type and shop, no tournament ID. The long type names are "Casual play", "Prerelease", "League Challenge" and "League Cup".
- **Layout:**
  - Up next shows month / weekday and day / time, with "Trainers" over the count;
  - agenda day headers look like the prototype;
  - the filter sheet has small grey section labels;
  - the filter bar line is as wide as its content.
- **Styling:** keep Tailwind, but name design values in `@theme` instead of long `[…]` values. `GoingButton` reads `auth` itself.

**Problems hit and how they were solved**
- *I staged files* with `git mv`/`git rm`. Bruno added deny rules for both (`c23d972`). Later a stray `git stash list` in a command was refused by the same list, and I reran it without that.
- *`inertiaProps('events.*.id')` returns null*, because there is no wildcard support. Fixed by reading the list and using `pluck('id')`.
- *A request method named `when()`* clashed with `Conditionable::when()` (fatal error). Renamed to `datePreset()`.
- *Larastan ignores `@param` on closures*, so scopes inside `when()` were "undefined". Fixed with a typed private method, `upcomingOfTypeAndDate()`.
- *The Playwright MCP needs Google Chrome*, which isn't installed. Screenshots went through the project's own Playwright in scratchpad scripts.
- *The coin sat under the prize cards on narrow phones* before the first flip. Fixed with `min-w-0 flex-1` on the message group.
- *JavaScript puts keys like "7" first in an object*, so the date presets came out of order. `DATE_PRESETS` is now a list.
- *The URL ends up as `type[0]=cup`.* This comes from Laravel's `fullUrl()` (`http_build_query`), not Inertia. The browser test reads the query with `parse_str`. An Inertia option tried first changed nothing and was removed.
- *A text click on "Filters" failed once the badge showed* ("Filters1"). Fixed with `data-testid="open-filters"`. An `aria-label` would have hidden the count from screen readers.
- *Tailwind 4 `leading-*` doesn't reach child elements* (the variable isn't inherited), and `text-2xl` brings its own line height. Fixed with `leading-none` on the span itself.
- *The ui-reviewer stopped on a network error* before its screenshots. It was resumed from where it stopped.
- *The fast-tick browser test passed with the bug*, because the local server answers before the second click.
  - Fix: `DB::listen(fn () => usleep(150_000))` slows the server like mobile data, which works because Pest's browser server runs in the test process.
  - Result: the test went red, then green after the fix.

**`/rethink-tests` outcome.** All 18 test files walked through.
- **Accepted:**
  - an explicit day in `filterableEvent()`, because a `static` counter was shared across tests;
  - the browser test also removes a chip and clicks Clear all (shown red by breaking `NO_FILTERS`);
  - the privacy check ignores letter case. `assertDontSee` is case-sensitive and the owner's email is lower case; shown red by leaking it.
- **Rejected:** a browser test for Pick dates (the server is fully tested, and the test would be slow), and any removals.
- **Moved to M06:** saving emails in lower case.

**Review outcome.** `code-reviewer`, `privacy-guard` and `ui-reviewer` (a re-check after the UI fixes). privacy-guard found no leaks.
- **Fixed:**
  - a 500 on `/?type[0][]=cup`;
  - repeated values giving duplicate chips, stale shop ids, and `from`/`to` echoed without "Pick dates";
  - no tie-breaker on equal start times, so the list could flicker;
  - quick ticks lost and typed dates wiped in the filter sheet. The sheet now keeps a draft, and typed dates apply once they're a whole day that isn't in the past;
  - the desktop dialog's 16px close button, a day heading read as one word, the date chip's label, and a 36px list button;
  - the privacy test now seeds a child profile and a birth year, and `architecture.md` describes the Arch tests accurately.
- **Not changed:** the sort inside count queries, the logo link height, and commit housekeeping.
- **Moved to later:** the card title naming (M09), and a partial-reload privacy case (when deferred props arrive).

**What is left**
- Push and open the PR.
- Bruno: check by hand on a real phone (filter sheet, typing dates, chips).

**Surprising**
- Laravel rewrites the query string of the page URL (`type[]` becomes `type[0]`, keys sorted), so a shared link never looks exactly like the one clicked.
- Inertia cancels a running visit when a new one starts, so "apply as you tick" needs a local draft or quick ticks are lost. It only shows on a slow network.
- Chrome reports every digit typed into a date box as a full date (`0002-12-05`), which the server rightly drops.

### 2026-11-19 · M04 · Domain schema and seed data

**Outcome.** All five acceptance criteria met. Ten v1 tables with models, enums, factories and their keys; `ends_at` and `is_child` computed by model saving hooks; four event scopes; a demo seeder mirroring the prototype. `composer verify` green: Pint, Larastan 0 errors, build, `tsc`, `vp check`, 23 Vitest, 118 Pest (7,962 assertions). Run in ten slices, each proposed, built test-first and committed by Bruno.

**Commits** (`main..HEAD`)
- `ab3ab31` drop divisions, seasons and "maybe" from the docs; estimates on EventType · `f81c2b1` start m04: slice plan
- `eb3102f` shops and events tables, enums, factories, unique keys (Slice 1)
- `50f896b` ends_at from the type's estimated hours on every save (Slice 2)
- `9c6bae7` upcoming, ofTypes, atShops, between scopes (Slice 3)
- `fc59382`, `2cf78a0` player profiles (`pop_id`), `is_admin` and reminders setting on users; docs (Slice 4)
- `9aef37e`, `f348d3c` child profiles need consent; 13+ account gate recorded for M06 (Slice 5)
- `60cc883` attendances (Slice 6) · `1048dfa` social accounts and magic login tokens (Slice 7)
- `f4d9a43` event follows and reports (Slice 8) · `c3cfe36` note: alerts as topics, grouped reports
- `8088365` import runs (Slice 9) · `3190f50` demo seeder (Slice 10)
- `7759e79` seeded Lisbon times stored in UTC (`/rethink-tests` P1) · `b97ca85` event deletion cascades (P2)
- Review fixes: `ec7d91c` Pint, `d017708` seeder children and crowd, `2717ab4` `between()` in UTC, `3b6522e` seeder guard, `f8e972d` saving-hook wording, `d5c7eda` user settings default, `3a02e59` safer under-13 rule, test gaps, Lisbon import time, shop deletion note

**Decisions by Bruno** (all in `decisions.md`)
- No divisions and no seasons (CLAUDE.md rule 3 rewritten); no "maybe": a row in attendances means going (rule 5 rewritten); `confirmed_at` kept for M12.
- End-time estimates on `EventType::estimatedHours()`, not a config file; enums as string columns with casts; scopes plural, taking lists.
- `notification_settings` holds only `reminders` (JSON, so it can grow); "event changed" and "possibly cancelled" are always sent; threshold emails stop by unfollowing.
- Player ID column is `pop_id`; `event_reports.type` instead of `kind`; `import_runs` counters `events_*`.
- Account holders must be 13+ (a sign-up checkbox, `users.age_confirmed_at`, built in M06); under-13s only as child profiles; `is_child` keeps its name.
- `is_child` is true when the person turns 13 or younger this year (the safer reading), refreshed by a yearly 1 January routine (later).

**Problems hit and how they were solved**
- *`make:enum` nested files* in `app/Enums/Enums/`: deleted and written by hand.
- *Migration tests passed before the code* (the `make:model -m` stubs already drop their tables): shown red by breaking a `down()` on purpose (Slices 1 and 9).
- *MySQL reorders JSON keys*: the defaults test compared with `toBe`; switched while there were four keys, back to `toBe` with one.
- *I staged files once* (`git add -N` to count lines): the call was denied; staging is Bruno's.
- *The kit's `WithoutModelEvents`* in `DatabaseSeeder` would have switched off both saving hooks: removed, shown by seeding failing on `ends_at` with the trait back.
- *The kit uses `CarbonImmutable`*: a `Carbon` return type in the seeder failed; typed `CarbonInterface`.
- *The production-guard test passed before the guard*: `db:seed` asks "Are you sure?" in production and cancels in tests; the test now runs `db:seed --force`, as a deploy script would.
- *Larastan rejected indexing the crowd* (`$crowd[$i]` may be null): replaced by slicing the crowd listed twice.

**`/rethink-tests` outcome.** All 11 test files walked through. Accepted: P1 (seeded Lisbon times stored in UTC, tested in July, when Lisbon is UTC+1), P2 (deleting an event deletes its answers, follows and reports; shown red by removing each cascade). Deferred to M05: the scopes combined (P4). Rejected: seeding once in `DemoSeederTest` (P3).

**Review outcome.** `code-reviewer` and `privacy-guard` (the `User` model is shared as `auth.user`); `ui-reviewer` not needed. privacy-guard: no leak today (no routes). Fixed: Pint failure; demo children aged out on 1 Jan (birth years now relative, seeder tests freeze time); `between()` ignored the bounds' time zone (Laravel binds the clock time); demo admin seeded anywhere (now `local`/`testing` only); hook guarantee narrowed to model saves; new users' settings `null` in memory (model `$attributes`); crowd at overlapping events (crowd of 60 in consecutive blocks); two test gaps; import time in Lisbon; shop-deletion behaviour recorded; seeder style. Moved to later milestones: duplicate Player ID errors and logs (M07), consenting-user rule and `consent_by_user_id` on delete (M07), `#[Hidden]` and a `CHECK` constraint (optional, later).

**What is left**
- Push and open the PR.
- Bruno: `php artisan migrate:fresh --seed` locally (wipes the local database), then the signed-in `/ui-check` with `family@preflight.test` / `password`.

**Surprising**
- Laravel writes a date's clock time into SQL without converting its time zone; with Lisbon equal to UTC in winter, the `between()` bug and a missing seeder conversion would both have stayed hidden until March.
- The kit's `DatabaseSeeder` mutes model events by default, which silently defeats model hooks.
- `db:seed` in production refuses to run without `--force`, which made a guard test pass for the wrong reason.

### 2026-11-18/19 · M03 · pokedata.ovh spike

**Outcome.** All three acceptance criteria met. Real responses saved as fixtures, `docs/pokedata.md` written (request and filter contract, field mapping, time, shops, questions for the author), docs and decisions aligned. `composer verify` green (also with `CI=true`): Pint, Larastan 0 errors, build, `tsc`, `vp check`, 23 Vitest, 66 Pest (7,033 assertions). First milestone run in slices (`/slice`), with Bruno committing each one.

**Commits** (`main..HEAD`)
- `1d6a79f` start m03: pokedata.ovh spike slice plan
- `b3cf756` record pokedata.ovh fixtures, define API returns and how we will stablish contracts (Slice 1)
- `95186c3` map pokedata.ovh fields to ImportedEvent and list questions for the author (Slice 2)
- `d10747e` changed venue denomination to shops. text only
- `c3d2dc4` alignd docs with pokedata findings: casual and prereleases types, shops and one guid. (Slice 3)
- `827f78e` check the pokedata fixture is a list of events with the importers fields (`/rethink-tests` P1)
- `7856f46` keep pokedata raw fixtures out of the formatter (`/verify`)

**What the spike found** (details in `docs/pokedata.md`)
- One request, `GET /events/api/_tcg/_country/PT`: 850 events up to 28 Feb 2027, no paging, plain JSON list.
- A bare `_tcg` filters nothing (byte-identical to `_country/PT`, VG and GO included). Cups and challenges carry a `product` field (`tcg`/`vg`/`go`); friendlies do not. The importer keeps `nonpremier TCG`, or League Challenge/Cup with `product: tcg`. Filtering on `type` alone would have let in 12 VG/GO tournaments.
- `nonpremier TCG` covers League nights and Friendlies; the website calls them "TCG Friendly". No prereleases listed (no set release due).
- `when` is local shop time without a zone; no end time; `name` empty for 723 of 816 kept events; `pokemon_url` event-specific (with the tournament ID) for 104 events, generic otherwise; shop spellings vary under one `league` ID.
- The website uses its own undocumented, paged endpoint with the same data; not used.

**Decisions by Bruno** (all in `decisions.md`): casual replaces Friendly and League; prerelease is its own type (Great Ball, casual keeps the Poké Ball); "shops", never "venue"; one `official_guid`; `ImportedEvent` without endsAt, `title` null when empty and shown as type + shop name; end-time estimates Casual 2h, Prerelease 3h, Challenge 4h, Cup 6h; `leagueId` kept for later; cost not used; Azores only a warning.

**Problems hit and how they were solved**
- *The branch was behind `main`* (PR #4 merged after it was created), so `/start-milestone` would have run on old rules. Stopped; Bruno fast-forwarded and restarted.
- *I believed a bare `_tcg` filtered TCG.* Bruno doubted the first result; two repeat requests (fresh, `cf-cache-status: DYNAMIC`, other filter order) confirmed it does not.
- *My first type filter was wrong* (any League Challenge/Cup counted as TCG). Comparing the website's endpoint with the API (Bruno's idea) exposed 12 VG/GO cups and challenges and the `product` field.
- *Slice 1 grew*: Bruno asked to record the findings before committing, so the request and filter contract went into `docs/pokedata.md` in Slice 1 (Plan line updated).
- *`vp check` wanted to reformat the raw fixtures*: `tests/Fixtures/**` added to the formatter's `ignorePatterns`, like `design/**`.
- *I asked twice whether "shop instead of venue" applied everywhere*; Bruno had already said so.

**`/rethink-tests` outcome.** P1 accepted: test 3 now requires a list of events, each with the 8 fields the importer reads (`toBeList()`, `->each->toHaveKeys()`), shown red with a wrong key first. Rejected: a test that the empty fixture is `[]` (M10's abort test covers it) and deleting the byte-identical `pt-all-games` fixture (evidence for the `_tcg` finding). Bruno's question on weeks without events: tests use saved fixtures; the live import asks for every future event, so a quiet week is not an empty result; a truly empty result aborts and emails admins.

**Review outcome.** `code-reviewer` only (no routes, props, mail or UI touched, so no privacy-guard or ui-reviewer). No blocking defect; all ACs met; every count in `pokedata.md` re-checked against the fixtures. Applied: `other` type removed (dropped at import), `ends_at_estimated` dropped and `ends_at` recomputed when starts_at or type changes, guid-vs-display_id matching for player submissions added as an open question, longitude figure removed, kept counts added (92 of 816 event-specific URLs), "friendlies" → casual, all 8 shop spelling pairs listed, `name` added to the fixture test, decision rows sorted. Declined: a `product` check in the test (M10's filter tests cover it), shop-spelling flips triggering "still going?" (Bruno: will not happen), commit-message typos (no history rewrite).

**What is left**
- Push and open the PR.
- Bruno: write to the pokedata.ovh author (questions at the end of `docs/pokedata.md`).

**Surprising**
- The public API ignores a bare `_tcg`, and an unknown sub-option (`_tcg/ftcg`) returns `[]` instead of an error: invalid filters never fail loudly.
- The website does not use the public API at all.
- The website's own warning: events are retrieved once, and later edits may not reach pokedata. This matters for the sync's change detection in M10.

### 2026-11-17 · M02 · Design tokens and layout shell

**Outcome.** All five acceptance criteria met. `composer verify` green: Pint, Larastan level 7 (0 errors), build, `tsc`, `vp check`, 23 Vitest tests, 60 Pest tests (222 assertions), including 8 browser runs at 390px and 1280px.

**What was done, file by file**

*Design tokens and fonts*
- `resources/css/app.css`: the ten colours, three font stacks and three radii from `design.md` as Tailwind 4 `@theme` variables (`bg-ink`, `text-muted`, `rounded-card`, `font-display` …). The kit's shadcn variables (`--primary`, `--border`, …) now point at these tokens, so kit components match. Single light look: the `.dark` palette is gone, `color-scheme: light` on `:root`, and the class-based `dark` variant is kept so `dark:` classes never fire. Base layer: body 15px / 1.45 on `bg-2`, red 2px focus outline with 2px offset on every `:focus-visible`, reduced motion (durations to 0.01ms so `animationend` still fires).
- `vite.config.ts`: fonts through the Laravel Vite plugin's `fonts` option with `google()` (IBM Plex Sans `sans`, Barlow Condensed `display`, IBM Plex Mono `mono`); downloaded at build and served from our domain. A `test` block for Vitest.
- `resources/views/app.blade.php`: page background `#f8fafc` before CSS loads; dark-mode class and detection script removed.

*Route and layout*
- `routes/web.php`: `/styleguide` registered only inside `if (app()->isLocal())` (`Route::inertia`).
- `resources/js/layouts/public-layout.tsx`: the playmat band (radial gradient, two dashed card outlines), navbar (AppLogo; "Sign in" and red "Sign up" pill for guests, "Sign out" when signed in, all 40px tall), optional `hero` slot, `<main>` (max 1100px), footer with the non-affiliation notice and placeholder links (privacy, terms, contact, add a missing event). A page uses it with `Page.layout = [PublicLayout, { hero: … }]`.
- `resources/js/app.tsx`: `initializeTheme()` no longer called.

*Components* (flat in `resources/js/components/`, kebab-case files like the kit)
- `app-logo.tsx`, `app-logo-icon.tsx`: the kit's files redrawn with the three fanned cards and "PRE-FLIGHT" in the display font; name from `config('app.name')`, truncates in narrow places.
- `tournament-type-icon.tsx`: the four balls exactly as in `design.md`, one component, `useId()` clip id per instance, `aria-hidden`.
- `tournament-type-badge.tsx`: built on the kit's `ui/badge`; icon plus visible name, Cup filled graphite.
- `responsive-dialog.tsx`: the design's "Sheet", built like shadcn's Responsive Dialog: Radix Dialog at 768px and up, vaul Drawer below (kit `useIsMobile()`), `autoFocus` on the drawer so the focus trap works, `aria-describedby={undefined}` when there is no description, 40px close button.
- `ui/button.tsx`: variants restyled (`default` red primary, `outline` = the design's ghost, `link` graphite with red underline and a 40px minimum height); only text, background and border colours animate.
- `ui/card.tsx`: the one card component, restyled (6px radius, line border, no shadow, display-font title).
- `ui/sonner.tsx`: the kit's toast, top right, 420px, 15px text, graphite, 2.6s, light theme; flash toasts still wired.
- `ui/drawer.tsx`, `ui/empty.tsx`: added with the shadcn CLI (`vaul` is the one new npm package; imports pointed at the kit's `@/lib/utils`).
- `resources/js/lib/time.ts`: `formatDay`, `formatTime`, `formatRelativeDay` in Europe/Lisbon with the browser's `Intl` (no date library). Upcoming events only.
- `resources/js/lib/tournament-types.ts`: `TOURNAMENT_TYPES`, `TournamentType`, `TOURNAMENT_TYPE_NAMES`.
- `resources/js/pages/styleguide.tsx`: every component in Card sections, the four balls and the rarity legend, the dialog, a toast button, the empty state, and a sample in the hero slot.

*Tests*
- `tests/Feature/StyleguideTest.php`: renders in `local` (status and Inertia component); 404 in `production`, `staging` and `testing` (AC2).
- `tests/Browser/StyleguideTest.php` + `tests/Browser/Datasets.php`: no horizontal scroll and every section at 390px and 1280px (AC1); dialog closes on Escape and returns focus, closes on a real backdrop click, keeps focus inside, each asserting the drawer on phones and the dialog on desktop (AC4).
- `resources/js/lib/time.test.ts`: November, July (summer hour), December, the Lisbon day boundary and the relative-day rules (AC5).
- `resources/js/components/tournament-type-icon.test.tsx`, `tournament-type-badge.test.tsx`: exact SVGs, `aria-hidden`, unique clip ids, visible type names (AC3).
- `tests/Pest.php`: `registerWebRoutesIn($environment)` helper for environment-only routes.
- `composer.json`: `npm run test` (Vitest via `vp test run`) in `verify`; `package.json` `test` script; `.gitignore` ignores browser failure screenshots.

*Process and docs*
- `CLAUDE.md`: base every implementation on official docs and check the kit and shadcn first; one test per defect at the lowest layer; remind Bruno each session how to follow the plan with `watch`.
- `.claude/commands/start-milestone.md`: writes the approved plan as a checklist in `progress.md` and keeps it ticked.
- `docs/design.md`, `docs/architecture.md`: component names, folders, Vitest, fonts and toast updated; `docs/decisions.md`: 30+ entries.

**Problems hit and how they were solved**
- *First plan reinvented things the kit already had* (custom Toast, custom Sheet, a controller, a `pf/` folder): Bruno asked for official docs and standards; reworked to the kit's Sonner, Button, Card and logo, shadcn Drawer/Empty, `Route::inertia`, flat folders. Became a CLAUDE.md rule.
- *The shadcn CLI used pnpm*: it left `pnpm-lock.yaml`, `corepack`, a duplicate `cn` package and `vaul` missing from `package-lock.json`. Cleaned up and reinstalled with `npm ci`.
- *My first route snippet failed Larastan* (missing `Inertia` import, 500 on request), as Bruno suspected; switched to the one-line `Route::inertia`.
- *Infinite render loop* from `setLayoutProps({ hero: <JSX/> })` during render, then *"Element type is invalid"* because a bare layout object with an element is read as named layouts. Fixed with the documented tuple `[PublicLayout, { hero }]`.
- *No focus trap on phones*: vaul defaults `autoFocus` to `false`, so focus never entered the drawer (found by the browser test; the option is only in vaul's source and types).
- *Backdrop test flaky with synthetic events* (Radix defers outside clicks to the real `click`): replaced with a real Playwright click at the overlay's corner through `$page->page()->locator()`.
- *`dump()` output swallowed* by the agent output format during debugging: debug data written to a scratch file instead.
- *CI red on the first PR run, green locally*: Vitest loads `vite.config.ts` in `serve` mode, and the Laravel Vite plugin refuses to run as a dev server when `CI` is set (never set locally). A `LARAVEL_BYPASS_ENV_CHECK=1` bypass was tried and dropped (Bruno, after Copilot's warning): it would also run dev-server code inside tests. Fixed the Vitest-documented way: under `process.env.VITEST` only the React plugin loads and `@/` comes from `tsconfig.json` (`resolve.tsconfigPaths`). Lesson: run `CI=true composer verify` before pushing changes to the toolchain.
- *Fonts missing in the running dev server*: `composer dev` was started before the font change; restart needed (production build was fine).

**`/rethink-tests` outcome.** Every test walked through with Bruno. Proposals P1–P6 (Tab count from focusables, toast on phone, Shift+Tab, DST switch day, navbar states, badge visibility) all rejected; tests kept. Standards check: the widths dataset moved to Pest's scoped `tests/Browser/Datasets.php`; kept on purpose: the Playwright position click, the `registerWebRoutesIn()` helper, `renderToStaticMarkup` instead of React Testing Library. Mutation checks done during the session: time zone set to UTC (3 summer tests red), fixed clip id (clip-id test red), `isLocal()` replaced by `true` (3 404 runs red), `useIsMobile()` forced to `false` (3 phone runs red).

**Review outcome.** privacy-guard: no findings. code-reviewer and ui-reviewer: 25 items merged; applied the drawer/dialog assertion, bold "Sign up", no red link hover, `aria-describedby`, instant focus ring, mixed-case titles, secondary action first, types moved to `lib/`, dead `app.tsx` case, logo `truncate`, single light look in three steps, 40px touch targets, and (Bruno's idea) one restyled `Card`. Accepted as is: "Sept" for September, 9px drawer corners and centred drawer title, 80% dialog backdrop. Declined: stronger AC1 check, DST switch-day test, invalid-date handling, footer width, splitting workflow changes.

**What is left**
- Push the branch and open the PR; merge after CI.
- Signed-in `/ui-check` with the family demo account once M04 seeds it.
- Bruno: restart `composer dev` to load the new fonts locally.
- Optional, offered and not decided: a browser test with `inDarkMode()` asserting the light look holds on a dark OS.

**Surprising**
- The browser tests earned their cost on day one: an infinite render loop, a wrong layout format and a missing focus trap on phones, none visible in the code review.
- vaul's `autoFocus` default (`false`) silently disables the focus trap; it is not in vaul's docs.
- Recent browser data writes September as "Sept" in en-GB.

### 2026-11-12/13 · M01 · Scaffold, tooling and CI

**Outcome.** All four acceptance criteria met. `composer verify` is green locally, from a fresh clone (`composer setup`) and in CI: 48 tests, 157 assertions, Larastan level 7 with 0 errors.

**What was done, file by file**

*Quality gate and scripts*
- `composer.json`: `verify` script runs, in order, `pint --test` → `phpstan analyse` → `npm run build` → `npm run types:check` → `npm run check` → `pest --parallel`. The build comes first because it generates the Wayfinder TS files (needed by `tsc`) and the Vite manifest (needed by the browser test). Scripts trimmed to `setup`, `dev`, `lint` (Pint fix), `verify`. A `conflict` keeps `brianium/paratest` below 7.28.
- `package.json`: kit script names kept (`build`, `dev`, `check`, `check:fix`, `types:check`); `playwright` added as a dev dependency.
- `vite.config.ts`: Markdown, `design/**` and `.claude/**` excluded from `vp fmt`; `design/**` excluded from `vp lint`.

*Tests*
- `phpunit.xml`: `Arch` and `Browser` test suites; `DB_DATABASE=tournament_preflight_test`; `INERTIA_SSR_ENABLED=false`. No `APP_KEY`: tests use the key in `.env`.
- `tests/TestCase.php`: `Http::preventStrayRequests()` in `setUp()`, so any unfaked outgoing request fails the test.
- `tests/Pest.php`: `Feature` and `Browser` use `TestCase` + `RefreshDatabase`; kit placeholders removed.
- `tests/Arch/ArchTest.php`: Pest `php` and `security` presets, plus no `dd`/`dump`/`ray`/`ddd`/`var_dump` in classes (AC3).
- `tests/Arch/NoDebugCallsInFilesTest.php`: the same ban for `routes/`, `config/`, `bootstrap/` and Blade views, which `arch()` cannot see.
- `tests/Feature/StrayRequestsTest.php`: asserts the guard is on, then that an unfaked `Http::get()` throws (AC4).
- `tests/Feature/TimezoneTest.php`: config and PHP both run in UTC (rule 4).
- `tests/Browser/SmokeTest.php`: opens `/` in Chromium; asserts the title contains the app name and there are no JS errors or console logs.
- `tests/Unit/ExampleTest.php` removed (`true is true`); `tests/Unit/.gitkeep` keeps the folder.

*Configuration*
- `.env.example`: `APP_NAME=Pre-Flight`, `APP_TIMEZONE=UTC` (with a "keep UTC" note), test database note.
- `config/app.php`: timezone read from `APP_TIMEZONE` (default `UTC`).
- `config/inertia.php`: SSR read from `INERTIA_SSR_ENABLED` (default `true`).
- `.github/workflows/tests.yml`: runs on `pull_request` and `push` to `main`, skips docs-only changes, cancels superseded runs. PHP 8.4, MySQL 8.4 service, Chromium, then `composer verify`.
- `.gitignore`: `CLAUDE.md` and `.claude/` are tracked; only `.claude/settings.local.json` is ignored.

*Claude kit and docs*
- `.claude/commands/rethink-tests.md` (new) and the `/rethink-tests` step in `workflow.md`, `start-milestone.md`, `CLAUDE.md`. `/wrap-up` now writes this type of documentation-ready log.
- `.claude/hooks/format.sh`: formats TS/CSS/JSON with `vp fmt` (Prettier is not installed); Markdown no longer auto-formatted.
- `CLAUDE.md`, `README.md`, `docs/architecture.md`, `docs/workflow.md`, `docs/milestones/M01-scaffold.md`: aligned with the installed kit (Pest 5, Larastan 7, Vite+, Tailwind kept, Herd + DBngin, `composer setup`); Tailwind-teaching and slow-tests notes in `CLAUDE.md`.
- `docs/decisions.md`: 15+ entries (versions and every choice above).

**Problems hit and how they were solved**
- *`pest --parallel` crashed* (`TypeError` in Pest's `WrapperRunner`): paratest 7.28 changed `SuiteLoader::$tests` to nested arrays; Pest 5.3.5 (latest) expects strings. Pinned paratest below 7.28 with a composer `conflict`.
- *41 tests failed with "No application encryption key"*: local `.env` had no key. First fixed with a test key in `phpunit.xml`; GitGuardian flagged it, so it was removed and `composer setup` (`key:generate`) is now the documented path.
- *12 tests failed only while `composer dev` ran*: Inertia SSR posted every render to the Vite dev server (`public/hot`), and the stray-request guard blocked it. SSR is off in tests.
- *`vp check` failed on docs, the prototype and `.claude/settings.json`*: excluded from formatting and linting.
- *First `dd()` experiment crashed the runner*: it was in a service provider, which runs on boot. Moved to a controller to see the real arch failure.
- *`main` had never been pushed*: GitHub made `m01-scaffold` the default branch and PR creation failed. Pushed `main` unchanged and set it as default.
- *`tournamentpreflight.test` did not resolve*: Herd's dnsmasq was not running. Bruno restarted Herd services; the site answers 200.
- *CI ran twice per commit*: `push` on all branches plus `pull_request`. Now `pull_request` + `push` to `main`.

**`/rethink-tests` outcome.** Every M01 test was walked through with Bruno. Experiments showed two real holes: a `dd()` in `routes/web.php` passed the arch test, and the smoke test passed on a 500 error page. Accepted all five proposals: P1 debug-call scan for non-class files, P2 guard checked before the request, P3 title assertion in the smoke test, P4 UTC test, P5 remove the placeholder unit test. Each new test was shown failing on a deliberately broken app first. Rejected: none.

**Review outcome.** `code-reviewer` found two must-fix items (AC1 and `decisions.md` inconsistent after the key removal), both fixed. Optional items done: CI duplicates, docs text, scripts. Declined by Bruno: `config.platform.php`, permission hardening in `.claude/settings.json`, tracking `boost.json`.

**What is left**
- PR #2 (review fixes, CI triggers, stronger tests, this wrap-up) to merge.
- Bruno: mark the GitGuardian incident for the old test key as resolved (it is in `main`'s history via the regular merge of PR #1; the key was never used).
- Bruno: set `APP_NAME=Pre-Flight` and `APP_URL=http://tournamentpreflight.test` in local `.env`.

**Surprising**
- The stray-request guard paid off on day one: it exposed Inertia SSR calling the dev server during tests.
- Pest 5.3.5 and paratest 7.28 are incompatible on release; worth checking on each `composer update`.

## Found along the way

<!-- Problems or ideas outside the current milestone. Each gets a milestone or "later". -->

- **Kit focus styles**: `ui/toggle.tsx`, `ui/select.tsx`, `ui/navigation-menu.tsx` and `app-header.tsx` still set `outline-none` with the kit's faint ring, hiding design.md's red focus outline; none is on a page players use yet. Fix when a page uses them. (M15)
- **Paratest pin**: remove the `conflict` on `brianium/paratest` when Pest supports 7.28+. (later)
- ~~**Starter kit demo pages**~~: handled in M06 (Slices 1a, 1b, 7).
- ~~Summary agent~~: decided 13 Nov, `/wrap-up` writes the full summary instead.
- **Kit dark mode leftovers**: the inline detection script and the `initializeTheme()` call are gone (M02 review), but the Settings → Appearance page, `HandleAppearance`, `use-appearance` and the kit's `dark:` classes remain, inert. Remove with the kit demo pages. (M06)
- **`fontaine`**: optional dev package for metric-matched fallback fonts (fewer layout shifts while fonts load); the build prints a hint. Ask before adding. (later)
- **Non-affiliation footer on every page** (CLAUDE.md rule 7): only `PublicLayout` has it; the kit's `welcome` page (`/`) and the auth and app layouts do not. Home page in M05, auth pages in M06. (from the M02 review)
- **`auth.user` shares the whole `User` model** with every Inertia page (`HandleInertiaRequests`). Only the owner receives it today, but once profiles, Player IDs or birth years touch `User`, share a small owner-only shape or a Data object instead. (M06, from privacy-guard)
- **`EventCardData.title` is unused and uses the enum names** ("Challenge at Carta Alta") while the UI says "League Challenge": when M09 first shows titles, build the fallback from the same names as `lib/tournament-types.ts`, or drop it. (M05 `/review`; M09)
- **Privacy test and partial reloads**: `NoPersonalDataLeaksTest` makes full page loads only. When a page gets an `Inertia::optional()` or `Inertia::defer()` prop, add a partial-reload case (the `X-Inertia-Partial-*` headers) with the same scans. (M05 `/review`; later)
- **Emails saved in lower case**: store and look up account emails lower-cased (`Zzleak@gmail.com` and `zzleak@gmail.com` are one inbox in practice; Laravel does not do it by default), wherever accounts are created and matched. (Bruno, M05 `/rethink-tests`; M06)
- **Signed-in `/ui-check`** of the M02 shell with the family demo account: possible now (`family@preflight.test` / `password` after `php artisan migrate:fresh --seed`; the kit's password login works until M06). (Bruno, M04)
- ~~**Tournament types in code**~~: done in M05 Slice 2.
- **Bigger events sanctioned as `nonpremier TCG`** would be listed as casual; find a way to spot them. (later)
- **Sync abort threshold**: "less than half the last good run" may trip on a seasonal lull (e.g. December); review when building it. (M10)
- **pokedata edits after first retrieval** (website warning): time or shop changes may never reach us; the change-detection flow in M10 should not rely on them alone. (M10)
- **Footer links** point to `#` until their pages exist: privacy and terms (M14), contact (M14), add a missing event (M11).
- ~~**Our components into `resources/js/components/pf/`**~~: done in M05 Slice 1.
- **Yearly `is_child` refresh**: a scheduled command on 1 January (Lisbon) that saves every child profile through the model, so `is_child` turns false for those who have aged out (`decisions.md`, M04 review). (M10, with the scheduler)
- **Alerts as topics, and grouped reports** (Bruno's idea): outgoing change and cancellation alerts are published once per event and fanned out to going players and followers, never stored per user; an `event_changes` log is proposed so emails can say what changed. Reports stay one row per user but are grouped by event and type in the admin list. Both are open questions in `decisions.md`. (M10, M11, M12)
- **Reports "mark handled"** (M11 scope) needs a column the data model does not have yet (e.g. `event_reports.handled_at`). Add it in M11. (M11)
- **Sync must save events one by one**: `ends_at` is written by the `Event` model's `saving` hook, and mass updates (`Event::where(…)->update([...])`) skip model events (Laravel docs, Eloquent "Mass Updates"), so they would leave `ends_at` stale. (M10)
- ~~**"Maybe" button in the styleguide**~~: relabelled "Going ✓" in M05 Slice 2.
