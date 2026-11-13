# M01 · Scaffold, tooling and CI

**Branch**: `m01-scaffold` · **Read**: `architecture.md` (Stack, Environments and CI), `workflow.md` (Testing)

## Goal
A clean Laravel 13 + React starter kit app with every guardrail in place, so later milestones only add features.

## Scope
- Confirm the scaffold Bruno created: Laravel 13, PHP 8.4, React starter kit, Pest, MySQL. Record the Inertia, React, Tailwind and Pest versions in `decisions.md`.
- `.env.example` for MySQL `tournament_preflight` and a `tournament_preflight_test` database; `phpunit.xml` uses the test database. `APP_TIMEZONE=UTC`, `APP_LOCALE=en`.
- Larastan at level 7 (`phpstan.neon`, kit default), Pint (Laravel preset), Vite+ `vp check` (Oxlint + Oxfmt) from the kit.
- `composer verify` script: `pint --test`, `phpstan analyse`, `npm run build`, `npm run types:check` (tsc --noEmit), `npm run check` (`vp check`), `pest --parallel`. Uses the kit's npm script names.
- Pest 5 browser plugin installed and one smoke browser test that loads `/`.
- `tests/Arch/ArchTest.php` with the presets that make sense (`php`, `security`) plus: no `dd`, `dump`, `ray`.
- `Http::preventStrayRequests()` in the base TestCase.
- GitHub Actions workflow running `composer verify` with a MySQL 8.4 service on push and PR.
- Laravel Boost installed if Bruno agrees (ask). If it writes guidelines into `CLAUDE.md`, keep them in their own tagged section below ours.
- Remove starter kit demo pages that will not be used later only if trivial; otherwise leave them for M06.

## Out of scope
Any product feature, styling, auth changes.

## Acceptance criteria
1. `composer verify` passes locally from a fresh clone after `composer setup` (install, `.env` from `.env.example`, `php artisan key:generate`, migrate, build).
2. The CI workflow passes on the PR.
3. A deliberate `dd()` in app code makes the arch test fail (try it, then remove it).
4. A test that calls `Http::get('https://example.com')` without a fake fails (try it, then remove it).

## Notes
- If the installer did not ask about Pest or MySQL, fix it here rather than re-scaffolding.
- Do not change the Larastan level later to get green.
