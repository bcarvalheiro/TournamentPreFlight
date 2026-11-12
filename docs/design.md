# Design spec

Visual direction: **Pre-Flight Pairings**, a TCG tournament hall. The top of the home page is a dark playmat; the next 5 events are a printed pairings sheet pinned to it; below, the event list is a day-by-day agenda of tear-off match slips with a sticky filter bar.

The working prototype is in `design/prototype/pairings.html` (with `app.js` for fake data and flows). Open it in a browser. It is the reference for layout, spacing, copy and interactions. Do not copy its code wholesale: rebuild it as typed React components with Tailwind. Where this document and the prototype differ, this document wins.

Mobile first (design at 390px wide), works up to desktop (content max width 1100px, agenda max 760px).

## Tokens

Define these once in `resources/css/app.css` as Tailwind 4 `@theme` variables and use only them.

| Token | Value | Use |
| --- | --- | --- |
| `--color-bg` | `#FFFFFF` | Cards, sheets |
| `--color-bg-2` | `#F8FAFC` | Page background |
| `--color-ink` | `#1F2933` | Text, navbar, playmat band, Cup type label |
| `--color-ink-2` | `#2B3742` | Hover on dark surfaces |
| `--color-muted` | `#5B6875` | Secondary text |
| `--color-line` | `#E1E7ED` | Borders |
| `--color-red` | `#E63946` | Actions, counts, focus ring, "live" |
| `--color-paper` | `#FBFBF8` | Pairings sheet |
| `--color-board-fg` | `#E9EEF3` | Text on the band |
| `--color-amber` | `#F6C453` | Reserved for warnings ("possibly cancelled") |

Fonts (Google Fonts, `display=swap`):

- Display: **Barlow Condensed** 600/700/800, uppercase, for the hero, day headers, type labels, big counts.
- Text: **IBM Plex Sans** 400/500/600/700, body at 15px / 1.45.
- Mono: **IBM Plex Mono** 500/600, for tournament IDs, the pairings sheet, slip labels.

Radii: cards 6px, sheets 14px, buttons 9px, chips fully rounded. Focus: 2px red outline, 2px offset, on every interactive element. Respect `prefers-reduced-motion`.

Contrast: red on white is 4.2:1, so use red for large text (counts) and for buttons with white bold text at 14px+ bold; never for small body text. Muted on bg-2 passes AA.

## Type icons ("balls")

Each tournament type has a ball icon, ranked common to rare. **Ship the official-looking set** (the prototype's "Classic" toggle), decided by Bruno on 12 Nov 2026 with the trademark risk known. There is no icon toggle in the app. The ball colours below are icon-only and are not part of the UI palette.

| Type | Rank | Icon |
| --- | --- | --- |
| Friendly | Common | Poké Ball (red top) |
| League | | Great Ball (blue top, red bands) |
| Challenge | | Ultra Ball (black top, yellow bands) |
| Cup | Rare | Master Ball (purple top, pink dots, white M) |

Build them as one `TypeBall` React component (`type`, `size`) from these SVGs (viewBox 0 0 24 24, `aria-hidden`, the type name is always shown as text next to it):

```svg
<!-- Shared clip for the top half. In React give it a unique id per instance (useId) -->
<clipPath id="ballTop"><path d="M1 12a11 11 0 0 1 22 0z"/></clipPath>
<!-- Friendly: Poké Ball -->
<path d="M1 12a11 11 0 0 1 22 0z" fill="#E3350D"/><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>
<!-- League: Great Ball -->
<path d="M1 12a11 11 0 0 1 22 0z" fill="#2F6BD8"/><g clip-path="url(#ballTop)" fill="#E3350D"><ellipse cx="5.6" cy="6.4" rx="4" ry="1.9" transform="rotate(-38 5.6 6.4)"/><ellipse cx="18.4" cy="6.4" rx="4" ry="1.9" transform="rotate(38 18.4 6.4)"/></g><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>
<!-- Challenge: Ultra Ball -->
<path d="M1 12a11 11 0 0 1 22 0z" fill="#222"/><g clip-path="url(#ballTop)" fill="#F5C518"><rect x="3.2" y="0" width="3.4" height="12"/><rect x="17.4" y="0" width="3.4" height="12"/><rect x="3.2" y="6.2" width="17.6" height="2"/></g><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>
<!-- Cup: Master Ball -->
<path d="M1 12a11 11 0 0 1 22 0z" fill="#7B3FA0"/><circle cx="6.3" cy="7.6" r="2.1" fill="#EC5FA6"/><circle cx="17.7" cy="7.6" r="2.1" fill="#EC5FA6"/><path d="M9.2 9V4.4l2.8 2.9 2.8-2.9V9" fill="none" stroke="#fff" stroke-width="1.4" stroke-linejoin="round"/><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>
```

A small legend under the filter bar shows the four icons from "Common" to "Rare".

## Brand mark

Three fanned card outlines (graphite, outlined, red) followed by "PRE-FLIGHT" in Barlow Condensed 800, uppercase. SVG in the prototype's `.brand`. The app name is **Pre-Flight** (`config('app.name')`), used in one `Brand` component. The hero headline stays "Pokémon Pre-Flight".

## Home page, top to bottom

### 1. Band (playmat)
Dark radial gradient on `ink`, with two faint dashed card outlines as decoration. Contains:

- **Navbar** (50px): brand left; right side signed out: "Sign in" (text) and "Sign up" (red pill); signed in: "Profiles (n)" and "Sign out".
- **Hero** (left column on desktop, 5/12): h1 "Pokémon Pre-Flight" on two lines, display font, clamp(2.4rem, 10vw, 4.2rem). Line: "Should I go or should I **stay at home?**" (the bold part red). Small line: "See how many players are heading to each Play! Pokémon tournament in Portugal before you sleeve up. Independent fan project."
- **Coin flip** (decorative fun, keep it): a red "GO" / light "STAY" coin that flips on tap with the line "Still can't decide? Flip for it. Heads you go." The prize-zone grid next to it is decoration, `aria-hidden`.
- **Pairings sheet** (right column on desktop, 7/12; below the hero on mobile): paper card rotated -0.8°, a red pin at the top, header "UP NEXT" plus "Posted 19:42". Columns When / Event / Trainers. Five rows: day and time (or NOW / LIVE in red), shop name, icon + TYPE · CITY in mono, red count, small "I'm going" button. Footer: "Headcount, not registration · Register at the shop".

### 2. Sticky filter bar
Sticks to the top on scroll, translucent `bg-2` with blur. Contents, horizontally scrollable on mobile: "Filters" pill (graphite, with a red badge showing the number of active filters), "Any time ▾" shortcut when no date filter is set, one removable chip per active filter (date, each type with its icon, each shop), and on the right "12 of 29 events" or "First 25 of 31 events".

**Filter sheet** (bottom sheet on mobile, centred dialog on desktop): When (2-column grid of presets, Pick dates reveals From / To date inputs, To cannot be before From, no past dates), Type (2x2 toggles with icons), Host shop (checkbox list with event counts), footer "Clear all" and "Show n events" (live count).

### 3. Agenda
Grouped by day. Day header: red left border, "SAT 15 NOV" in display font plus a relative label ("Tomorrow", "In 3 days", "Next week"). On ≥700px the header sits in a 120px left column and is sticky.

**Match slip card** (one per event): white, 4px graphite top border, 6px radius. Left part: top row "ID 25-11-000123" in mono and the type label (icon + name; Cup label is filled graphite, others outlined); shop name bold; row with city, "Starts 10:00" or red "● Happening now", long type name; for multi-profile accounts a red line "You: Ana, Tiago". Right stub (104px), separated by a dashed tear line with two punched half-circles: big red count, "TRAINERS" label, full-width red "I'm going" button (outlined once going; label "Going ✓" for one profile, "Going (2)" for several).

A "possibly cancelled" event shows an amber "Possibly cancelled, check with the shop" line on the slip.

### 4. Disclaimer and footer
Pink-tinted box: "**A headcount, not a registration.** Tapping "I'm going" tells other players you plan to attend. Register with the host shop as usual." Footer: non-affiliation notice, links to privacy, terms, contact, "Add a missing event".

## Shared components

- **Sheet**: bottom sheet on mobile, dialog on desktop. Close button, Escape, backdrop click, focus trap, returns focus. Use the shadcn Dialog/Drawer underneath, restyled.
- **ProfilePicker**: list of the account's profiles, each with name, division for this event and a going checkbox; "Save". Shows "Add a profile" link.
- **Toast**: bottom centre, graphite, 2.6s, `role="status"`.
- **Buttons**: primary (red fill, white bold), ghost (outlined graphite), link.
- **Empty state**: muted centred text with one red action.

## Other pages

Use the same band (navbar only, no hero) and the same card language:

- **Event page**: big type label and icon, shop, date/time, a counts block styled like the pairings sheet (Junior / Senior / Masters rows, Maybe, Confirmed), answer controls per profile, follow form, official link button, disclaimer.
- **Sign in / Sign up**: one sheet or page with Google, Apple, Discord buttons (provider icons from the providers' own brand guidelines) and an email field for the magic link.
- **Profiles**: list of profile cards (name, Player ID, birth year, current division), add/edit form, consent checkbox for under-13s.
- **Admin**: plain and functional; tables in the same tokens.

## Copy rules

English, short, friendly, trainer flavour without official terms: "trainers" for players in counts, "I'm going", "Maybe", "Still going?". Never imply registration or a reserved seat. Dates "Sat 15 Nov", times 24h "10:00".
