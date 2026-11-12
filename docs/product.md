# Product spec

## The question it answers

"If I go to this tournament, will there be enough people?" Pre-Flight shows, for every upcoming sanctioned Play! Pokémon tournament in Portugal, how many players say they are going. Shops do not need to do anything for it to work.

It is **a headcount, not a registration**. It reserves no seat, takes no payment, and players still register with the shop as usual. Every event page says so.

## Users

| Who | What they do |
| --- | --- |
| Guest | Browses events and counts, opens event pages, shares links |
| Player (account holder) | Signs in, manages profiles, answers "I'm going" / "Maybe" per profile, follows events, gets emails |
| Parent | A player whose account also holds profiles for their children (under 13 have no login of their own) |
| Admin | Moderates player-submitted events, sees import runs, hides wrong events |
| Shop | Not in v1. Later, optional (see "Later phase") |

## Accounts and profiles

- Login methods: Google, Apple, Discord, email magic link. No passwords.
- One account (a login) holds one or more **player profiles**. A parent has their own profile plus one per child.
- Profile fields: name, Player ID, birth year. All required.
- Player ID: digits only, unique across the app. Exact length is unconfirmed (see `decisions.md`); validate 1–10 digits until confirmed.
- A profile whose birth year makes the person under 13 this calendar year is a child profile. Creating one requires the parent to tick a consent statement; the time and the account are stored.
- **Division** (Junior, Senior, Masters) is derived from birth year and the season of the event, using the `seasons` table. 2027 season (to confirm against official rules): Junior born 2015 or later, Senior 2011–2014, Masters 2010 or earlier.

## Events

- Types shown in v1: **Friendly, League, Challenge, Cup** (League Challenge and League Cup on the long label). Other types from the import are stored but not listed.
- **Pokémon TCG tournaments only.** Video game and GO events are not imported or listed.
- An event has: date and start time, end time (from the source, or estimated by type), official tournament ID, type, host shop (venue) and city, official listing link, status.
- Statuses: `pending` (submitted, awaiting moderation), `listed`, `hidden` (admin), `cancelled`. Separately, a "possibly cancelled" flag set by the sync.
- "Upcoming" means the event has not ended yet, so ongoing events stay in the list until they finish. Past events never show in lists; their pages stay reachable by link and say the event has ended.

## Attendance

- Answers per profile per event: **going**, **maybe**, or none. Changing an answer updates the same row; removing it deletes the row (or sets not_going, see architecture).
- The public count is **going** only. Maybe is shown separately on the event page.
- Cards show the total going. The event page splits going by division (Junior, Senior, Masters) and shows maybe and confirmed counts: "12 going (8 confirmed) · 4 maybe".
- **Others see counts only, never names.** The account holder sees which of their own profiles are going.
- Answers can only be given for listed events that have not ended.

### The quick "I'm going" button (cards and pairings sheet)

1. Signed out: open sign-in. After signing in, come back to the same page and continue with step 2 for that event. If the account has no profile yet, create the first profile, then continue.
2. Signed in with one profile: toggle that profile's going answer. Show a short confirmation ("You're going to Carta Alta, Sat 15 Nov").
3. Signed in with several profiles: open a picker listing each profile with a checkbox for going. Save applies the choices per profile. The button then reads "Going (2)".
4. The button on the card only sets going. Maybe is chosen on the event page.

## Home page

See `design.md` for the visual spec. Content:

- Small navbar: signed out shows Sign in and Sign up; signed in shows Profiles (n) and Sign out.
- Hero: "Pokémon Pre-Flight" and "Should I go or should I stay at home?", one line saying what the app is.
- **Up next** (the pairings sheet): the next 5 upcoming or ongoing events by start time, ignoring filters.
- **Event list**: up to 25 upcoming and ongoing events, grouped by day, after filters. The count line says "12 of 29 events" or "First 25 of 31".
- Filters, all multi-select and combined with AND between groups: type (Friendly, League, Challenge, Cup), host shop (only shops with upcoming events, with their counts), date range (Any time, This week, This weekend, Next 7 days, Next 30 days, Pick dates with From and To). The date filter applies before the 25 cap. Filters live in the URL query string so a filtered view can be shared.
- Card: date and start time, tournament ID, type with its icon, shop name and city, total going count, quick "I'm going" button. "Happening now" replaces the start time for ongoing events.
- Empty state: "Nothing matches these filters" with Clear filters.
- Disclaimer line under the list and the non-affiliation footer.
- Later, not v1: city or region filter.

## Event page

Shareable URL (`/events/{id}-{slug}`). Shows: type, title, shop, address, date and time in Lisbon time, tournament ID, link to the official listing, counts (going by division, maybe, confirmed), the viewer's own answers with "I'm going" / "Maybe" / clear per profile, follow with a threshold ("tell me when 16 are going"), "Report a change or cancellation", the headcount disclaimer. Open Graph tags so shared links preview with the count. Past events show "This event has ended" and no buttons.

## Adding a missing event

A signed-in player pastes the official Event Locator link of a tournament and fills in type, date, start time, shop name and city. The app extracts the official Guid from the link when it can. If an event with the same Guid or link already exists, the player is sent to it. Otherwise it is saved as `pending` and an admin approves, edits or rejects it. The submitter gets an email when it is approved.

## Notifications (email)

- **Day-before reminder**: the day before the event, each account with a going answer gets one email listing its profiles: "Still going?" with Yes and No buttons (signed links, no login needed). Yes sets confirmed; No removes the going answer.
- **Event changed**: when the sync changes an event's date, time or venue, going answers become unconfirmed and the same "still going?" email goes out, saying what changed.
- **Possibly cancelled**: when the sync flags an event, followers and going players get one email suggesting they check with the shop.
- **Threshold**: a follower is emailed once when the going count first reaches their number. Optionally also when it drops back below (setting on the follow).
- **Moderation**: submitter is told when their event is approved or rejected.
- All emails have an unsubscribe link per category.

## Privacy and brand

- Data kept: account email and name, linked logins, profile name, Player ID, birth year, answers, follows.
- Past answers are anonymised 90 days after the event (the profile link is removed, counts stay).
- Account deletion removes the account, its profiles and answers.
- Privacy policy and terms pages (English), a contact address for data requests.
- Not affiliated notice on every page: "Not affiliated with, endorsed or sponsored by Nintendo, Creatures, GAME FREAK, The Pokémon Company or Play! Pokémon."
- No official artwork or logos, except the type icons, which use the official-looking ball designs (Bruno's decision).

## Out of scope for v1

Payments, Pokémon account login, pairings and results, deck lists, native apps, capacity and waitlists, shop accounts, real-time websockets, Portuguese translation.

## Later phase: shop tools (not now)

A shop claims its events by email magic link, sees going players' names and Player IDs only for players who agreed to share with that shop, confirms attendance with players, and exports a list for typing into TOM (TOM has no import). Tables added then: shops, shop_user, claimed_by_shop_id on events, per-shop consent records. Nothing in v1 should block this, but nothing in v1 builds it.
