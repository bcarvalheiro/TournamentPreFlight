# Decisions and open questions

Append new decisions at the bottom of the log with the date and milestone. Never rewrite an old entry; supersede it with a new one.

## Decision log

| Date | Decision | Why |
| --- | --- | --- |
| 2026-11-12 | Headcount app, not registration; no payments; independent of Pokémon | Core idea from the project plan |
| 2026-11-12 | Shops not needed in v1; shop tools are a later, optional phase | Works without anyone's adoption |
| 2026-11-12 | Login with Google, Apple, Discord, email magic link; no passwords; no Pokémon login (none public) | Plan |
| 2026-11-12 | One account holds several player profiles; under-13s only as parent-managed profiles with recorded consent | Portuguese digital consent age is 13 (Lei 58/2019 art. 16) |
| 2026-11-12 | Others see counts only, never names or Player IDs | Privacy, many players are children |
| 2026-11-12 | Division from birth year and the event's season via a `seasons` table | Limits change every season |
| 2026-11-12 | Stack: Laravel 13, PHP 8.4, MySQL 8.4 LTS, Inertia + React starter kit, Pest | Supported versions; smooth Laravel 14 upgrade |
| 2026-11-12 | **UI language is English** (overrides the plan's "Portuguese interface") | Bruno's decision |
| 2026-11-12 | Events from a nightly pokedata.ovh import behind one `EventSource` interface, plus player-submitted events with admin moderation; no scraping of the official locator | Only permitted routes; locator terms likely forbid scraping |
| 2026-11-12 | Sync: create/update; date/time/venue changes unconfirm answers and send "still going?"; missing events flagged "possibly cancelled", answers kept | Agreed sync flow |
| 2026-11-12 | Home page as specified in `product.md`; visual direction "Pre-Flight Pairings" | Chosen from four prototypes |
| 2026-11-12 | Ball icons per type (Friendly < League < Challenge < Cup); ship only our own simplified set | Trademark safety (superseded below) |
| 2026-11-12 | Tests run on MySQL, not SQLite | Behaviour matches production |
| 2026-11-12 | No websockets in v1; counts refresh after the user's action and on page load (optional 60s poll on the event page) | Simplicity |
| 2026-11-12 | Past answers anonymised 90 days after the event | Data minimisation |
| 2026-11-12 | **Ship the official-looking ball icons** (prototype "Classic" set: Poké, Great, Ultra, Master Ball), not our own set. Supersedes the entry above | Bruno's decision; the trademark risk was flagged to him and accepted |
| 2026-11-12 | **App name is "Pre-Flight"** (brand mark and `config('app.name')`); hero still reads "Pokémon Pre-Flight" as designed | Bruno's decision |
| 2026-11-12 | **Pokémon TCG tournaments only.** Import only TCG events; no `game` column | Bruno's decision |
| 2026-11-12 | M01: installed versions: Laravel 13.42, Inertia 3 (inertia-laravel 3.7, @inertiajs/react 3.11), React 19.3, Tailwind 4.3, Pest 5.3, Larastan 3.15, Boost 2.10 | Recorded at scaffold |
| 2026-11-12 | M01: **keep Tailwind 4**, not Bootstrap 5. Bruno knows Bootstrap, not Tailwind; Claude explains Tailwind as it builds the UI | The starter kit, shadcn/ui and `design.md` tokens are all Tailwind; switching would mean rebuilding the kit's UI |
| 2026-11-12 | M01: Pest 5 (kit version) instead of the planned Pest 4 | Newer and what the kit ships; no reason to downgrade |
| 2026-11-12 | M01: Larastan stays at level 7 (kit default) instead of the planned 6 | Stricter is fine; the level is never lowered |
| 2026-11-12 | M01: JS/TS lint and format with the kit's Vite+ (`vp check`: Oxlint + Oxfmt) instead of ESLint + Prettier | Avoids two linters and two formatters |
| 2026-11-12 | M01: CI runs PHP 8.4 (production target) even though local PHP is 8.5 | Match production |

## Open questions

Decisions for Bruno (Claude: do not decide these; use the stated default and flag it):

- [x] ~~App name~~: "Pre-Flight" (decided 12 Nov). Domain still open.
- [x] ~~Games at launch~~: TCG only (decided 12 Nov).
- [ ] **Hosting and email provider.** Needed by M15. Default: Mailpit locally, `log` mailer elsewhere until chosen.
- [ ] **Anonymisation period.** Default 90 days.

To verify before relying on them:

- [ ] pokedata.ovh response fields and refresh behaviour; write to its author about this use. (Spike M03.)
- [ ] 2027 division birth years against official Play! Pokémon rules (source so far: playnews.gg).
- [ ] Player ID format (length, digits only). Default validation: 1–10 digits.
- [ ] Whether Laravel 13 lists MySQL 8.4 as supported.
- [x] ~~Inertia version shipped by the starter kit~~: Inertia 3 (recorded M01).
- [ ] Terms of use covering the official Event Locator.
- [ ] Text of Lei 58/2019 art. 16 in the Diário da República (only summaries read).
