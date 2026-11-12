# M02 · Design tokens and layout shell

**Branch**: `m02-design-shell` · **Read**: `design.md` (all), prototype `design/prototype/pairings.html`

## Goal
The visual foundation every page uses: tokens, fonts, the band with navbar, the footer, and the base components.

## Scope
- Tokens from `design.md` as Tailwind 4 `@theme` variables; fonts loaded.
- `PublicLayout` with the band (navbar only variant and hero variant slot), footer with non-affiliation notice and links (privacy, terms, contact, add event: placeholders for now).
- Components in `resources/js/components/pf/`: `Brand`, `TypeBall` (the official-looking ball set), `TypeLabel`, `Button` variants (primary, ghost, link), `Sheet` (bottom sheet on mobile, dialog on desktop), `Toast`, `EmptyState`.
- `resources/js/lib/time.ts` with formatters (`formatDay`, `formatTime`, `formatRelativeDay`) using `Europe/Lisbon`, with unit tests in Vitest (add Vitest if the kit lacks it; it is the one new dev dependency allowed in this milestone).
- A dev-only `/styleguide` route (local environment only) showing every component and the four type balls.

## Out of scope
Home page content, data, auth.

## Acceptance criteria
1. `/styleguide` renders all components at 390px and 1280px with no horizontal scroll (browser test at both widths).
2. `/styleguide` returns 404 when `APP_ENV=production`.
3. `TypeBall` renders the four types with the exact SVGs from `design.md` (unique clipPath id per instance, so two balls on one page both render correctly), each with `aria-hidden` and visible type text in `TypeLabel`.
4. `Sheet` closes on Escape and backdrop, traps focus, returns focus (browser test).
5. Time formatters produce "Sat 15 Nov" and "10:00" for a UTC instant that is 10:00 in Lisbon, including across the DST change (unit tests for a date in July and one in December).

## Notes
Run `/ui-check /styleguide` before review.
