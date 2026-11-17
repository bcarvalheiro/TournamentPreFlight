# Progress

Claude updates this file at the end of every session (`/wrap-up`). Read it at the start of every session.

## Current

- **Milestone**: M02 · Design tokens and layout shell (review).
- **Branch**: m02-design-shell
- **State**: all five acceptance criteria met and tested; `/rethink-tests`, `/review` and `/ui-check` done; `composer verify` green (23 Vitest, 60 Pest, Larastan 0 errors). Next: push and open the PR, merge after CI. Then M03.

### Plan (M02)

Tests (written first, each shown failing):
- [x] Feature: renders the styleguide in the local environment
- [x] Feature: returns 404 for the styleguide outside local (production, staging, testing)
- [x] Browser: renders every component without horizontal scroll (390px, 1280px)
- [x] ~~Browser: unique clip ids~~ dropped: the Vitest clip-id test catches the same bug (one test per defect)
- [x] Browser: closes the dialog on Escape and returns focus to the trigger (both widths)
- [x] Browser: closes the dialog when the backdrop is clicked (both widths)
- [x] Browser: keeps keyboard focus inside the open dialog (both widths)
- [x] Vitest: time formatters (Nov, July, December, Lisbon day boundary, relative days)
- [x] Vitest: TournamentTypeIcon exact SVGs and unique clip ids
- [x] Vitest: TournamentTypeBadge visible type name

Implementation:
- [x] Tokens in `app.css`, shadcn variables mapped, light look only
- [x] Fonts via the Laravel Vite plugin (`google()`, `@fonts`)
- [x] Local-only `/styleguide` route
- [x] `lib/time.ts`
- [x] TournamentTypeIcon, TournamentTypeBadge, AppLogo
- [x] Button variants, ResponsiveDialog (Dialog + vaul Drawer), Sonner toast, shadcn Empty
- [x] PublicLayout (band, hero slot, footer); pages pick it with a layout tuple (the default-layout case was removed in review)
- [x] Styleguide page
- [x] JS tests in `composer verify`
- [x] Toast moved to top right, larger (Bruno, after seeing the styleguide)
- [x] `/rethink-tests`: proposals P1–P6 all rejected by Bruno (tests kept as they are). Standards check: widths dataset moved to the scoped `tests/Browser/Datasets.php` (Pest docs); kept on purpose: Playwright's `page()->locator()->click(position)` for the backdrop, the `registerWebRoutesIn()` helper (built from documented pieces, no documented alternative), and `renderToStaticMarkup` instead of React Testing Library (revisit when components get behaviour)
- [x] `/review` (code-reviewer, privacy-guard, ui-reviewer; ui-reviewer also did the `/ui-check /styleguide` at 390px and 1280px, signed out). Applied: drawer/dialog check in AC4 tests, bold "Sign up", no red hover on links, `aria-describedby` when no description, instant focus ring, mixed-case titles, secondary action first, tournament types moved to `lib/`, dead `app.tsx` case removed, `truncate` on the logo name. Accepted as is (Bruno): "Sept", 9px drawer corners and centred drawer title, 80% dialog backdrop. Declined: AC1 heading check (decided in `/rethink-tests`), DST switch-day test, invalid-date handling, footer width, splitting workflow changes. Open: single light look (#2) and touch targets (#4), see below. privacy-guard: no findings
- [x] Review #2: single light look in three steps (keep the class-based `dark` variant, never add `.dark`, `color-scheme: light`)
- [x] Review #4: touch targets raised to 40px (drawer close, Sign in/Sign up, link button)
- [ ] Signed-in `/ui-check` with the family demo account: after M04 seeds it (Bruno)
- [x] Cards: shadcn `Card` restyled with the tokens and used for the styleguide sections (Bruno)
- [x] Bruno checked the styleguide in the browser ("The rest seems fine. Really fine!")
- [x] `/wrap-up`

## Milestones

| # | Milestone | Status | Notes |
| --- | --- | --- | --- |
| M01 | Scaffold, tooling and CI | done | PR #1 and #2 merged |
| M02 | Design tokens and layout shell | review | All ACs met; PR to open. Signed-in `/ui-check` after M04 |
| M03 | pokedata.ovh spike | todo | |
| M04 | Domain schema and seed data | todo | |
| M05 | Home page on seeded data | todo | |
| M06 | Sign in: magic link and Google | todo | |
| M07 | Player profiles | todo | |
| M08 | "I'm going" and Maybe | todo | |
| M09 | Event page | todo | |
| M10 | Nightly import and sync | todo | |
| M11 | Submitted events and admin | todo | |
| M12 | Email notifications | todo | |
| M13 | Apple and Discord login | todo | |
| M14 | Privacy, legal and account deletion | todo | |
| M15 | Launch hardening | todo | |

Status values: todo, in progress, review, done.

## Session log

<!-- Newest first. One entry per session: date, milestone, what was done, what is left, anything surprising. -->

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
- `.claude/commands/rethink-tests.md` (new) and the `/rethink-tests` step in `workflow.md`, `start-milestone.md`, `CLAUDE.md`. `/wrap-up` now writes this kind of documentation-ready log.
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

- **Paratest pin**: remove the `conflict` on `brianium/paratest` when Pest supports 7.28+. (later)
- **Starter kit demo pages** (`dashboard`, `settings`, password auth pages and their 44 tests) are still in place; replace or remove in M06 with the new auth.
- ~~Summary agent~~: decided 13 Nov, `/wrap-up` writes the full summary instead.
- **Kit dark mode leftovers**: the inline detection script and the `initializeTheme()` call are gone (M02 review), but the Settings → Appearance page, `HandleAppearance`, `use-appearance` and the kit's `dark:` classes remain, inert. Remove with the kit demo pages. (M06)
- **`fontaine`**: optional dev package for metric-matched fallback fonts (fewer layout shifts while fonts load); the build prints a hint. Ask before adding. (later)
- **Non-affiliation footer on every page** (CLAUDE.md rule 7): only `PublicLayout` has it; the kit's `welcome` page (`/`) and the auth and app layouts do not. Home page in M05, auth pages in M06. (from the M02 review)
- **`auth.user` shares the whole `User` model** with every Inertia page (`HandleInertiaRequests`). Only the owner receives it today, but once profiles, Player IDs or birth years touch `User`, share a small owner-only shape or a Data object instead. (M06, from privacy-guard)
- **Footer links** point to `#` until their pages exist: privacy and terms (M14), contact (M14), add a missing event (M11).
