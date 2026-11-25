# Progress

Claude updates this file at the end of every session (`/wrap-up`). Read it at the start of every session.

## Current

- **Milestone**: M08 · "I'm going" (in progress).
- **Branch**: m08-attendance
- **State**: Plan approved (Bruno, 24 Nov). Next: Slice 1.

### Plan (M08)

Decisions approved with the plan (24 Nov): body `{profiles: {id: bool}}`; `SetAttendance` alone checks ownership (403) and answerable events (422); `own` prop uses a slim `OwnAttendanceData` (not `OwnProfileData`, whose keys would trip the privacy test); toasts built in the browser with `lib/time.ts`; the browser applies the pending intent with the same PUT; PUT throttled `throttle:30,1`.

- [x] Slice 1: `PUT /events/{event}/attendance` with `{profiles: {id: bool}}`; `SetAttendance` creates the row (`firstOrCreate`) or deletes it; someone else's trainer 403; pending, hidden, cancelled or ended event 422; several trainers in one transaction (`routes/web.php`, `Account/AttendanceController@update`, `Http/Requests/AttendanceRequest`, `Actions/SetAttendance`, `architecture.md` Code layout; `SetAttendanceTest`) · AC1, AC3, AC4
  - Changed (Bruno, 24 Nov): `firstOrCreate` instead of `createOrFirst`, which the Laravel 13 docs do not describe; the unique key still blocks a second row, and `wasRecentlyCreated` serves Slice 2.
- [x] Slice 2: the Action dispatches `CheckThresholds` (no-op until M12) once per save that changed an answer, after commit (`app/Jobs/CheckThresholds.php`, `SetAttendance`, `AttendanceController`, `M08-attendance.md`, `decisions.md`; `CheckThresholdsDispatchTest`) · scope
  - Changed (Bruno, 24 Nov): `SetAttendance::handle(user, event, {profile id: going})` takes the set of answers, decides every refusal before writing (an unknown id is now 403 like someone else's trainer), saves in one transaction and dispatches once; the controller makes one call. Reason: `Queue::fake()` ignores `afterCommit()`, and one entry point means no caller (M12's email links) can forget the dispatch.
- [x] Slice 2b: no follows and no thresholds (Bruno, 25 Nov): remove `CheckThresholds` and its dispatch; drop `event_follows` with a new migration; remove `EventFollow`, its factory and follow tests; docs everywhere the rule reaches (`SetAttendance`, migration, `EventReportsTest` (was `EventFollowsAndReportsTest`), `EventDeletionTest`, `architecture.md`, `product.md`, `design.md`, `decisions.md`, milestones M08/M09/M12/M14; `MigrationsTest` "has no event_follows table") · scope
- [x] Slice 3: home `own` prop for the account only: its trainers (id, name) and who is going to each upcoming event; `null` for guests; counts follow the answers; privacy test extended (`HomeController`, `Data/Private/OwnAttendanceData.php`, `types/`, `NoPersonalDataLeaksTest`; `HomeOwnAttendanceTest`) · AC2, AC6
  - Changed (Bruno, 25 Nov): `own` is built in `OwnAttendanceData` from each trainer's answers (Larastan needed typed lists); `PendingIntentTest`'s no-query check now loads Settings → Profile instead of home (decisions.md, 25 Nov; a third adjustment means rethinking that test).
- [x] Slice 4: quick button toggles for a solo player: partial reload (`only: events, upNext, own`), toast "You're going to Carta Alta, Sat 15 Nov", "Going ✓" outlined; signed out or not a player yet keeps M06's POST (`going-button.tsx`, `event-list-item.tsx`, `pairings-sheet.tsx`, `home.tsx`; `going-button.test.tsx`, `tests/Browser/GoingTest.php`) · AC2
- [x] Slice 4b: one message for every rate limit on a save (Bruno, 25 Nov): back to the page with "Too many tries. Wait 42 seconds and try again." (Laravel's `Retry-After`) instead of the bare 429 page; the going button shows its success toast only when the answer really changed (`bootstrap/app.php`, `going-button.tsx`, `decisions.md`; `TooManyRequestsTest`, `GoingTest` case) · found while testing Slice 4
  - Changed (Bruno, 25 Nov): the three older limit tests (sign-in, trainer add/edit, welcome form) now expect the redirect with the error toast instead of a bare 429; the sign-in limit gets the same message.
- [x] Slice 5: family accounts open `ProfilePicker` (checkbox per trainer, Save, "Add a trainer"); one PUT with per-trainer choices; "Going (2)"; "You: Ana, Tiago" on slips (`components/pf/profile-picker.tsx`, `going-button.tsx`, `event-list-item.tsx`, `design.md`; `profile-picker.test.tsx`, `GoingTest` family case) · AC4
  - Changed (Bruno, 25 Nov): no `profile-picker.test.tsx`: the sheet's content is portalled and only exists once open, so static markup cannot show it; the picker is tested in `GoingTest` (browser), and Vitest covers the family labels and the "You:" line (`going-button.test.tsx`, `event-list-item.test.tsx`). The picker uses big toggle rows instead of checkboxes (Bruno: checkboxes are hard on mobile; a select box cannot pick several on a phone).
- [x] Slice 6: resume after sign-in: `pendingIntent` sets a solo account going (never off) and opens the picker for a family (`home.tsx`, `going-button.tsx`; `tests/Browser/ResumeGoingTest.php`) · AC5
  - Changed (25 Nov): one `ProfilePicker` owned by `home.tsx` (the event is often on both the sheet and the agenda, so the slips cannot each open one); `ResponsiveDialog` and `ProfilePicker` take an optional `trigger`, the picker a `defaultOpen`. An event no longer on the page: solo still applies ("You're going."), a family gets nothing.

### Plan (M07)

- [x] Slice 1: save an adult profile: name required, Pokemon Player ID (POP ID) 1–10 digits and unique across accounts ("This Pokemon Player ID (POP ID) is already on another profile."), birth year 1920 to this Lisbon year; profile routes behind `auth` + `verified`; saving redirects with `redirect()->intended(route('home'))`; a social account without a password saves too (`routes/web.php`, `Account\PlayerProfileController@store`, `StorePlayerProfileRequest`, `Actions\CreatePlayerProfile`, `architecture.md` Code layout; `CreatePlayerProfileTest`) · AC2, AC4, AC5
  - Changed (23 Nov): "later profiles go to the list" moved to Slice 4, which builds the list.
- [x] Slice 2: child consent: one rule, `PlayerProfile::needsParentalConsent()`, used by the model and the request; no consent, no profile; with consent `consent_given_at` and `consent_by_user_id` (the signed-in owner) are stored (`PlayerProfile.php`, `StorePlayerProfileRequest`, `CreatePlayerProfile`; `ChildProfileConsentTest`) · AC3
- [x] Slice 3: the create form: name, Pokemon Player ID (POP ID), birth year; consent box shown for a child birth year (the server sends `consentFromYear` from the new `PlayerProfile::consentFromBirthYear()`); "Not now" kept; heading "Add a player" with "You, or your child if they are under 13.", so a parent who does not play can add only their child (`routes/web.php`, `PlayerProfileController@create`, `PlayerProfile.php`, `pages/profiles/create.tsx`; props in `CreatePlayerProfileTest`, `tests/Browser/AddPlayerTest.php` (renamed `AddTrainerTest.php` in review)) · AC3, AC4
  - Changed (Bruno, 23 Nov): offered as 3a (form) and 3b (consent box), kept as one slice (~250 lines); a browser test added because Vitest renders static markup only and the box appears as you type. The page's Vitest file was dropped (Bruno): the Inertia Vite plugin bundles every `pages/**/*.tsx`, tests included.
  - Changed (Bruno, 23 Nov): copy for a parent who does not play; was "Create your player profile" or "Add a profile".
- [x] Slice 4: profile list: cards with name, Pokemon Player ID (POP ID), birth year, only the account's own; saving a profile that is not the first returns to the list (`routes/web.php`, `PlayerProfileController@index/store`, `app/Data/Private/OwnProfileData.php`, `types/profiles.ts`, `pages/profiles/index.tsx`, `NoPersonalDataLeaksTest` case; `PlayerProfileListTest`, `CreatePlayerProfileTest`) · AC1
  - Changed (Bruno, 24 Nov): `Data/Own` renamed `Data/Private`, beside `Data/Public`.
- [x] Slice 4b: profile limits: a second profile aged 13 or older is refused, and a fourth child is refused with a pointer to support; the form tells the parent before they type (`StorePlayerProfileRequest`, `PlayerProfile::MAX_CHILDREN`, `User::canAddAdultProfile()`/`canAddChildProfile()`, `PlayerProfileController@index/create`, `config/app.php` `support_email` + `.env.example` `SUPPORT_EMAIL`, `pages/profiles/create.tsx`, `pages/profiles/index.tsx`, `docs/product.md`; `ProfileLimitsTest`) · AC7
  - Added (Bruno, 24 Nov): new rule found while testing slice 4. The support address lives in `.env` (the repo is public).
- [x] Slice 5: signed-in navbar "Trainers (n)", "Settings", "Sign out"; a no-profile notice on every page under the band ("Add a trainer to say you're going: you, or your child." + "Add a trainer"), not on the create page (`layouts/public-layout.tsx`, `HandleInertiaRequests` `auth.profilesCount`, `types/auth.ts`; `public-layout.test.tsx`, `SharedProfilesCountTest`) · AC4, AC5
  - Changed (Bruno, 24 Nov): profiles are "Trainers" in the UI (navbar, list page, "Add a trainer", messages); `ProfileNoticeTest` became `SharedProfilesCountTest` plus the layout test. The M06 query test in `PendingIntentTest` now ignores the navbar's one `count(*)` (Bruno).
- [x] Slice 6: edit a profile: Pokemon Player ID (POP ID) unique except itself; turning into a child asks consent, an existing child keeps its consent; the limits of Slice 4b apply when the birth year changes; `PlayerProfilePolicy` gives 403 to anyone but the owner; "Edit" on each card; the form is shared with "Add a trainer" (`routes/web.php`, `PlayerProfileController@edit/update`, `PlayerProfileRequest` (renamed from `StorePlayerProfileRequest`), `Actions\UpdatePlayerProfile`, `app/Policies/PlayerProfilePolicy.php`, `components/pf/profile-form.tsx`, `pages/profiles/edit.tsx`, `create.tsx`, `index.tsx`, `architecture.md`; `UpdatePlayerProfileTest`, `profile-form.test.tsx`) · AC1, AC2, AC3
  - Changed (Bruno, 24 Nov): offered as 6a (server) and 6b (page), kept as one slice (~350 lines); one request class for store and update.
- [x] Slice 7: delete a profile from the list with a confirmation dialog; the policy decides; its attendances are deleted; the last profile may go (`PlayerProfileController@destroy`, `PlayerProfilePolicy`, `pages/profiles/index.tsx` with `ResponsiveDialog`, `routes/web.php`; `DeletePlayerProfileTest`, `tests/Browser/DeleteTrainerTest.php`) · AC1, AC6
  - Changed (24 Nov): no `DeletePlayerProfile` Action: the operation is `$profile->delete()` and the foreign key cascades; an Action can come with M14's account deletion.
- [x] Slice 8: age asked again after a takeover: `RevokeUnprovenAccess` clears `age_confirmed_at`; the create page shows "I am 13 or older" when it is empty; saving requires it and sets it (`RevokeUnprovenAccess.php`, `PlayerProfileRequest`, `CreatePlayerProfile`, `PlayerProfileController@create`, `profile-form.tsx`, `create.tsx`, `architecture.md` users, `decisions.md`; `TakeoverAgeTest`, `profile-form.test.tsx`) · scope (takeover)

## Milestones

| # | Milestone | Status | Notes |
| --- | --- | --- | --- |
| M01 | Scaffold, tooling and CI | done | PR #1 and #2 merged |
| M02 | Design tokens and layout shell | done | PR #3 merged. Signed-in `/ui-check` after M04 |
| M03 | pokedata.ovh spike | done | PR #5 merged |
| M04 | Domain schema and seed data | done | PR #6 merged |
| M05 | Home page on seeded data | done | PR #7 merged |
| M06 | Sign in: email and password, Google, Discord, X | done | PR #8 merged |
| M07 | Player profiles | done | PR #9 merged |
| M08 | "I'm going" | in progress | "and Maybe" dropped: no maybe (decisions.md, 19 Nov) |
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

### 2026-11-23/24 · M07 · Player profiles

**Outcome.** All seven acceptance criteria met and tested.
- An account manages its trainers (player profiles, called "Trainers" in the UI): list, "Add a trainer", edit and delete with a confirmation. The navbar shows "Trainers (n)", "Settings" and "Sign out".
- Name, Pokemon Player ID (POP ID) and birth year are required. The POP ID is 1–10 digits and unique across accounts, and the duplicate message never says whose it is.
- A trainer under 13 needs the parent's consent box; when and by whom is stored. "Child" is decided from the birth year today, never the stored `is_child`, which goes stale on 1 January.
- Account limits: one trainer aged 13 or older, at most 3 children. The fourth child's message points to the support email, which lives in `.env` because the repo is public.
- Sign-in still lands on `/`. An account without trainers sees a notice linking to "Add a trainer". From "I'm going", the gate leads to the form and saving returns to the blocked page with the intent.
- After an account takeover, "Add a trainer" asks "I am 13 or older" again and saves the answer with the trainer in one transaction.
- Adding and editing is throttled (`throttle:10,1`). Form errors are tied to their fields for screen readers.

`composer verify` green: Pint, Larastan 0 errors, build, `tsc`, `vp check`, 78 Vitest, 301 Pest (9,516 assertions). Built in 9 planned slices (4b added), then `/review` with 9 fixes (A–I), each committed by Bruno.

**Commits** (`main..HEAD`)
- Slices:
  - `1e40ffe` player profiles with a no-profile warning (plan)
  - `05b2dda` save a profile and return to the blocked page; Player ID renamed POP ID (1)
  - `8606619` tidy the POP ID rename and format routes
  - `7246663` parental consent for a child (2)
  - `31f6b86` "Add a player" form with the consent box (3)
  - `4d9a6db` the account's profile list (4)
  - `6a73d3a` one adult and three children (4b)
  - `c1b2310` "Trainers" in the UI
  - `6b6e551` navbar links and the no-trainer notice (5)
  - `19dcaae` slice guide raised to about 350 lines
  - `bbb67a0` edit a trainer with a shared form (6)
  - `dbb165e` delete a trainer and their answers (7)
  - `6495f1e` age asked again after a takeover (8)
- `/review` fixes:
  - `c0fcd26` child decided from the birth year today (A)
  - `92cd051` age transaction and another account on the edit page (E), plus the throttle route (H)
  - `d2462d9` short validation messages and the stronger AC5 test (G, E)
  - `c9f16e8` form errors tied to fields, 40px inputs, tidy trainer pages (B, C, D, F), plus the throttle test (H)
  - `818eeb6` review decisions and trainer names in the docs (I)
- Outside the plan: `b61fb2c` a pulsing mail icon on the unverified-email notice in Settings and on `/email/verify` (Bruno's request after the review)

**Commit scope notes.**
- `05b2dda` carries the Player ID → POP ID rename across 28 files beside slice 1.
- `19dcaae` (the slice guide in `CLAUDE.md` and `.claude/commands/slice.md`) has no Plan line.
- Slice 6 came to about 517 changed lines, over the new 350 guide; Bruno kept it as one slice.
- The throttle (H) is split: its route change is in `92cd051` and its test in `c9f16e8`. `d2462d9` also carries the stronger AC5 test, which shares a file with the messages test.
- `b61fb2c` is settings and auth UI, outside M07's scope.

**Decisions by Bruno** (all in `decisions.md`)
- Profiles are "Trainers" in the UI; sign-in lands on `/` with a notice (AC4 reworded).
- A parent who does not play holds only child profiles; one adult and 3 children per account, more by email to support.
- Own-data shapes in `app/Data/Private/`.
- A slice is about 350 changed lines.
- Routes grouped by middleware, then by controller with `prefix` and `name` (saved as a preference).
- Two tabs passing the limits at once is accepted for now.

**Problems hit and how they were solved**
- A Vitest file under `resources/js/pages/` broke the build: the Inertia Vite plugin bundles every `pages/**/*.tsx`. Page behaviour is tested in components or the browser instead.
- The browser's own validation blocked the form, in Portuguese: `noValidate` on the trainer form, plus `attributes()` for readable field names.
- `Rule::when(false, …)` still passed the field into `validated()`: the false branch is `['exclude']`.
- The navbar wrapped at 360–390px: `whitespace-nowrap` and smaller text and padding below `sm`.
- Bruno found while testing that an account with a child could add a second adult: slice 4b added the limits.
- The review found that the stale `is_child` blocked editing a child who turned 14 and kept them counted: fix A.
- The navbar's `count(*)` broke the M06 query test: that test now ignores it (Bruno).
- Browser tests need `composer dev` running or a fresh `npm run build`.
- Clicking "Resend verification email" 7 times in a few seconds gave a bare 429 page: Fortify's `throttle:6,1` worked, but the page invites repeat clicks. Noted for M15.

**`/rethink-tests` outcome.** Skipped for M07 (Bruno, 24 Nov).

**Review outcome.** `code-reviewer`, `privacy-guard` and `ui-reviewer`; 9 fixes, all applied.
- **Fixed:**
  - the stale `is_child` (A)
  - form accessibility: aria links, consent error contrast, the age box lead-in (B)
  - 40px inputs (C)
  - notice rules on the list page and before verification (D)
  - test gaps: AC5 really answers, the age transaction, another account on the edit page (E)
  - layout polish (F)
  - short messages (G)
  - the throttle (H)
  - docs and the browser test rename (I)
- **Accepted, not fixed:** two tabs passing the limits; the policy has no `view` (the edit page is guarded by `update`).

**What is left.** Bruno pushes and opens the PR. Then M08 ("I'm going" and Maybe), which acts on the pending intent and uses the trainers.

**Surprising.** The year boundary: `is_child` is right on save and wrong from 1 January, so every rule now reads the birth year.

### 2026-11-21/23 · M06 · Sign in: email and password, Google, Discord, X

**Outcome.** All six acceptance criteria met and tested.
- Email and password through Fortify (registration with the "I am 13 or older" box, email verification, password reset). Two-factor auth, passkeys, magic-login tokens, the dashboard and Settings → Appearance are gone.
- "Continue with Google / Discord / X" through Socialite, with one pair of generic routes restricted by `whereIn`. Only providers with credentials show a button; X is ready but not configured.
- The player gate: anyone may browse; only a player (verified email + player profile, password optional) answers. After "I'm going", a user who is not ready is walked through verifying and adding a Pokemon Player ID (POP ID), and the gate remembers the page.
- The pending intent: a guest's "I'm going" is a POST that stores the event in the session, signs in, and returns to the same page, which receives the intent once. M08 acts on it.
- The auth pages are in the Pre-Flight look.

`composer verify` green: Pint, Larastan 0 errors, build, `tsc`, `vp check`, 68 Vitest, 232 Pest (9,055 assertions). Built in 11 planned slices (6 and 7 split in two), then `/rethink-tests` and 13 review fixes, each committed by Bruno.

**Commits** (`main..HEAD`)
- Plan: `e775106`, `6795a9e`
- Slices:
  - `e067c4d` no 2FA, passkeys or magic links (1a)
  - `4686047` no dashboard or Appearance (1b)
  - `857edb5` age box (2)
  - `5c85653` player gate (3)
  - `9a24db4` pending intent (4)
  - `252c95c` social sign-in (5)
  - `cde51a9` welcome page (6a)
  - `a727e68` social sign-up (6b)
  - `8dc9f86` auth look (7a)
  - `b1b72a6` social buttons (7b)
- `/rethink-tests`: `c5a0fed` social sign-in returns to the intended page; `321c1b5` expired or mismatched sign-in
- `/review` fixes:
  - `09a5e93` a social sign-up needs a provider-vouched email
  - `eb81c6b` proving the email signs out unproven sessions
  - `b343de9` guest "I'm going" is a POST for upcoming events, and shareable going links are planned
  - `6180f18` Larastan type on `SocialProvider::values()`
  - `af7eb07` the gate keeps the blocked page as the intended URL
  - `2f88007` privacy test covers the sign-in pages
  - `da29812` real logout and rate-limit tests
  - `b0ffb4d` pending sign-ups expire; refused provider codes
  - `772d2c9` gate opens `profiles.*` and the named `settings`; no extra intent query
  - `50ddef1` focus outline and 3:1 field borders
  - `6fa741c` tap targets, status text, copy
  - `d52901c` "Sign in to say you're going" and sign-up hints
- Housekeeping: `de2bcb0`, `08e99f9`, `fbf72d5` (Playwright output out of git)

**Commit scope notes.**
- `252c95c` (slice 5) also carries the Boost update that `composer require` ran (`CLAUDE.md` guidelines, `.claude/skills/socialite-development`).
- The `/rethink-tests` and `.gitignore` commits have no Plan line.
- `b343de9` accidentally added Playwright snapshots; `fbf72d5` removed them.
- `09a5e93` contains an early `ProvenEmailTest.php` that only passes from `eb81c6b` on.

**Decisions by Bruno** (all in `decisions.md`)
- **Sign-in methods:** email and password, plus Google, Discord and X. Apple in M13. No magic link, no 2FA, no passkeys.
- **Accounts:**
  - `users.name` stays required.
  - A password is optional for social accounts; they can add one in Settings → Security.
  - A social sign-up needs an email the provider vouches for; otherwise register with email and a password.
- **Gate:** anyone browses; the gate acts only on the way to answering. A password is optional for social accounts.
- **Linking:**
  - Link a social identity only when the provider vouches for the email.
  - An account belongs to whoever proves its email: proving it signs out every session set up before.
- **Pending intent:**
  - It lives in the session, never in the URL.
  - Shared going links (M09) open a pop-up; only a tap marks going.
- **Sessions** stay on the database driver.
- **WCAG** on the auth pages: focus outline, 3:1 borders, 40px targets.

**Problems hit and how they were solved**
- Wayfinder's `.form()` types broke after `wayfinder:generate` without `--with-form`. It must always run with `--with-form`.
- Google OAuth accepts only `localhost`, so Google is tried locally through `php artisan serve` at `http://localhost:8000`. Herd's `.test` host works for the other providers.
- `returnTo()` used `url('home')` (the path `/home`). Fixed to `route('home')`, with absolute URLs kept and checked against our host.
- The mixed-case email test could not fail because of the case-insensitive collation, so Bruno dropped it.
- A conflicted `git stash pop` kept the stash and staged files; resolved by hand and the stash dropped.
- Testing a refused provider code: a driver built inside the test keeps the test's request (no session). A Guzzle `MockHandler` passed through the provider's `guzzle` config works.
- Laravel's `full()` rebuilds `?type[]=cup` as `?type%5B0%5D=cup` when the gate saves the page. It's the same filters, and the tests build the expected value with `http_build_query`.
- The kit's `outline-none` hid design.md's global focus outline on inputs and checkboxes; Tailwind 4's `transition-colors` faded the outline in on links.
- `.gitignore` had `./playwright-mcp`, which ignores nothing.

**`/rethink-tests` outcome.** All 19 changed test files walked through by acceptance criterion.
- **Accepted:** the intended-page assertion for social sign-in; the expired/mismatched-state test with the real drivers, plus its fix.
- **Tried and dropped:** the mixed-case email test (it could not fail).
- **Kept:** exact-message and session-shape assertions.
- **Noted for M07:** Settings → Security for social accounts.

**Review outcome.** `code-reviewer`, `privacy-guard` and `ui-reviewer`; 25 findings.
- **Fixed:**
  - takeover paths: a typed email on the welcome page (prevented by the vouched-email rule); a claimer's session surviving (`RevokeUnprovenAccess`)
  - GET intent planting (now POST, upcoming events only)
  - the page lost through the gate
  - the privacy test not covering the sign-in pages
  - weak AC6 tests
  - pending sign-up expiry, a 500 on a refused code, welcome throttle
  - the gate blocking M07's routes and `/settings`
  - an extra query per request
  - focus, contrast, tap targets, status text, copy, and the context and age hints
- **Dropped:** #14 (lower-case the typed email): there's no typed email now.
- **Moved:**
  - #22 `aria-invalid` / `aria-describedby` → M15
  - #24 Inertia history encryption → M14
- **Accepted as is:** #25, pre-M06 accounts without `age_confirmed_at` (there are no production accounts).

**What is left**
- Bruno opens the PR and merges it; then M07 (player profiles, the first-profile form, and the age question after a takeover).
- X needs credentials before its button shows.
- Screenshots and hand checks are listed in the PR text.

**Surprising**
- The strongest fix came from changing the flow rather than adding code. Requiring a vouched email removed a whole class of account takeover, and the welcome page shrank.
- Chat previews and browser prefetch open links, so a link that changes state would inflate the counts. That shaped the share-link design for M09.

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
- Pokemon Player ID (POP ID) column is `pop_id`; `event_reports.type` instead of `kind`; `import_runs` counters `events_*`.
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

**Review outcome.** `code-reviewer` and `privacy-guard` (the `User` model is shared as `auth.user`); `ui-reviewer` not needed. privacy-guard: no leak today (no routes). Fixed: Pint failure; demo children aged out on 1 Jan (birth years now relative, seeder tests freeze time); `between()` ignored the bounds' time zone (Laravel binds the clock time); demo admin seeded anywhere (now `local`/`testing` only); hook guarantee narrowed to model saves; new users' settings `null` in memory (model `$attributes`); crowd at overlapping events (crowd of 60 in consecutive blocks); two test gaps; import time in Lisbon; shop-deletion behaviour recorded; seeder style. Moved to later milestones: duplicate Pokemon Player ID (POP ID) errors and logs (M07), consenting-user rule and `consent_by_user_id` on delete (M07), `#[Hidden]` and a `CHECK` constraint (optional, later).

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

- **Resend verification email invites repeat clicks** (Bruno, 24 Nov): Fortify already throttles the resend route (`throttle:6,1`, `config('fortify.limiters.verification')`), but after a send the button keeps pulsing and only a plain line confirms it, so Bruno clicked 7 times (6 emails in 5 seconds) and got the bare 429 page. The 429 page part is fixed app-wide by M08 Slice 4b ("Too many tries. Wait n seconds and try again."). Still to do: after a send, disable the button with "✓ Link sent. Resend in 0:59" and stop the pulse; a Pest test that pins the limit (6 pass, the 7th is 429). Consider tightening to 1 a minute and 6 an hour with a named Fortify limiter. (M15)

- **Error text colour**: the kit's `InputError` uses `text-red-600` (#e7000b), not a design token; it is 4.77:1 on white but fails on tinted boxes (M07 moved the consent error out of the pink box). Add a darker error token (e.g. #B42318) and use it app-wide. (M15)
- **Linked accounts seeing a child's profile** (Bruno's idea, 24 Nov): today a child profile belongs to exactly one account. Later, another account (a second parent, or the child's own account once they turn 13) could be allowed to see or manage it. `OwnProfileData` is the owner's view and could be reused; needs a link table and consent rules. (later)
- **Browser validation on the auth pages**: register, sign in, welcome and reset use `required`, so the browser blocks the form with its own bubble in the browser's language (Portuguese for most players, against rule 8) before our English errors show. M07 put `noValidate` on the profile form; decide the same for the kit's auth forms. (M15)
- **Pairings sheet shop names** are cut with an ellipsis at 390px ("Toca do T…"); try a smaller font or two lines. (from the M06 browser checks; later)
- ~~**Settings → Security for social accounts**~~: done in M06 fix 10b ("Email me a link to set a password").
- **Kit focus styles**: `ui/toggle.tsx`, `ui/select.tsx`, `ui/navigation-menu.tsx` and `app-header.tsx` still set `outline-none` with the kit's faint ring, hiding design.md's red focus outline; none is on a page players use yet. Fix when a page uses them. (M15)
- **Paratest pin**: remove the `conflict` on `brianium/paratest` when Pest supports 7.28+. (later)
- ~~**Starter kit demo pages**~~: handled in M06 (Slices 1a, 1b, 7).
- ~~Summary agent~~: decided 13 Nov, `/wrap-up` writes the full summary instead.
- ~~**Kit dark mode leftovers**~~: removed in M06 Slice 1b (Appearance page, `HandleAppearance`).
- **`fontaine`**: optional dev package for metric-matched fallback fonts (fewer layout shifts while fonts load); the build prints a hint. Ask before adding. (later)
- ~~**Non-affiliation footer on every page**~~: auth pages use `PublicLayout` since M06 Slice 7a.
- **`auth.user` shares the whole `User` model** with every Inertia page (`HandleInertiaRequests`). Only the owner receives it today, but once profiles, Pokemon Player IDs (POP IDs) or birth years touch `User`, share a small owner-only shape or a Data object instead. (from privacy-guard; still open after M07: profiles reach the page only through `OwnProfileData` and the `auth.profilesCount` number, and `User` gained no personal fields; M14)
- **`EventCardData.title` is unused and uses the enum names** ("Challenge at Carta Alta") while the UI says "League Challenge": when M09 first shows titles, build the fallback from the same names as `lib/tournament-types.ts`, or drop it. (M05 `/review`; M09)
- **Privacy test and partial reloads**: `NoPersonalDataLeaksTest` makes full page loads only. When a page gets an `Inertia::optional()` or `Inertia::defer()` prop, add a partial-reload case (the `X-Inertia-Partial-*` headers) with the same scans. (M05 `/review`; later)
- ~~**Emails saved in lower case**~~: not needed. `users.email` uses the case-insensitive `utf8mb4_unicode_ci` collation, so uniqueness and lookups already ignore case; social lookups also lower-case. Bruno dropped the mixed-case test in M06 because it could not fail.
- **Signed-in `/ui-check`** of the M02 shell with the family demo account: possible now (`family@preflight.test` / `password` after `php artisan migrate:fresh --seed`; the password login still works after M06). (Bruno, M04; later)
- ~~**Tournament types in code**~~: done in M05 Slice 2.
- **Bigger events sanctioned as `nonpremier TCG`** would be listed as casual; find a way to spot them. (later)
- **Sync abort threshold**: "less than half the last good run" may trip on a seasonal lull (e.g. December); review when building it. (M10)
- **pokedata edits after first retrieval** (website warning): time or shop changes may never reach us; the change-detection flow in M10 should not rely on them alone. (M10)
- **Footer links** point to `#` until their pages exist: privacy and terms (M14), contact (M14), add a missing event (M11).
- ~~**Our components into `resources/js/components/pf/`**~~: done in M05 Slice 1.
- **Yearly `is_child` refresh**: a scheduled command on 1 January (Lisbon) that saves every child profile through the model, so `is_child` turns false for those who have aged out (`decisions.md`, M04 review). (M10, with the scheduler)
- **Alerts as topics, and grouped reports** (Bruno's idea): outgoing change and cancellation alerts are published once per event and fanned out to going players, never stored per user; an `event_changes` log is proposed so emails can say what changed. Reports stay one row per user but are grouped by event and type in the admin list. Both are open questions in `decisions.md`. (M10, M11, M12)
- **Reports "mark handled"** (M11 scope) needs a column the data model does not have yet (e.g. `event_reports.handled_at`). Add it in M11. (M11)
- **Sync must save events one by one**: `ends_at` is written by the `Event` model's `saving` hook, and mass updates (`Event::where(…)->update([...])`) skip model events (Laravel docs, Eloquent "Mass Updates"), so they would leave `ends_at` stale. (M10)
- ~~**"Maybe" button in the styleguide**~~: relabelled "Going ✓" in M05 Slice 2.
