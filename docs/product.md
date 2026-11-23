# Product spec

## The question it answers

"If I go to this tournament, will there be enough people?" Pre-Flight shows, for every upcoming sanctioned Play! Pokémon tournament in Portugal, how many players say they are going. Shops do not need to do anything for it to work.

It is **a headcount, not a registration**. It reserves no seat, takes no payment, and players still register with the shop as usual. Every event page says so.

## Users

| Who | What they do |
| --- | --- |
| Guest | Browses events and counts, opens event pages, shares links |
| Player (account holder) | Signs in, manages profiles, answers "I'm going" per profile, follows events, gets emails |
| Parent | A player whose account also holds profiles for their children (under 13 have no login of their own) |
| Admin | Moderates player-submitted events, sees import runs, hides wrong events |
| Shop | Not in v1. Later, optional (see "Later phase") |

## Accounts and profiles

- Login methods: email and password (with email verification), Google, Discord, X; Apple later (M13). A social sign-up needs an email the provider confirms; without one the player registers with email and a password.
- Anyone can look at every page. Only a player answers: a signed-in account with a verified email and at least one player profile (the Player ID is required). Pressing "I'm going" before that walks the account through verifying the email and adding a Player ID, then back to the page. A password is optional for social sign-ups; they can add one in Settings. A signed-in account without a profile is not redirected: it lands on the home page, and every page shows a notice linking to "Create your player profile". The navbar links to Profiles and Settings.
- Only people aged 13 or older can have an account: sign-up asks them to confirm it (self-declared; it cannot be verified). Under-13s exist only as child profiles on a parent's account.
- One account (a login) holds one or more **player profiles**. A parent has their own profile plus one per child.
- Profile fields: name, Player ID, birth year. All required.
- Player ID: digits only, unique across the app. Exact length is unconfirmed (see `decisions.md`); validate 1–10 digits until confirmed.
- A profile whose birth year means the person may be under 13 at some point this calendar year (they turn 13 or younger) is a child profile. Only the birth year is known, so someone turning 13 this year counts as a child all year: asking for consent too often is harmless, too rarely is not. Creating one requires the parent to tick a consent statement; the time and the account are stored.
- No divisions (Junior, Senior, Masters) and no seasons: Pre-Flight does not use them. Birth year is kept only to tell child profiles apart.

## Events

- Types shown in v1: **Casual, Prerelease, Challenge, Cup** (League Challenge and League Cup on the long label). Casual covers League nights and Friendlies, which pokedata does not tell apart. Any other type is dropped at import; there is no "other" type.
- **Pokémon TCG tournaments only.** Video game and GO events are not imported or listed.
- An event has: date and start time, end time (estimated by type: Casual 2h, Prerelease 3h, Challenge 4h, Cup 6h; the source has none), official tournament ID, type, title (when the source has none, shown as type and shop name), host shop and city, official listing link, status.
- Statuses: `pending` (submitted, awaiting moderation), `listed`, `hidden` (admin), `cancelled`. Separately, a "possibly cancelled" flag set by the sync.
- "Upcoming" means the event has not ended yet, so ongoing events stay in the list until they finish. Past events never show in lists; their pages stay reachable by link and say the event has ended.

## Attendance

- Answers per profile per event: **going** or not going. Going is a row; not going is no row (removing the answer deletes the row). There is no "maybe".
- The public count is the number of going answers.
- Cards show the total going. The event page shows the going and confirmed counts: "12 going (8 confirmed)". How confirmation works is decided later (M12).
- **Others see counts only, never names.** The account holder sees which of their own profiles are going.
- Answers can only be given for listed events that have not ended.

### The quick "I'm going" button (cards and pairings sheet)

1. Signed out, or signed in without a verified email or a Player ID: sign in, verify the email and add the first profile as needed, then come back to the same page and continue with step 2 for that event.
2. Signed in with one profile: toggle that profile's going answer. Show a short confirmation ("You're going to Carta Alta, Sat 15 Nov").
3. Signed in with several profiles: open a picker listing each profile with a checkbox for going. Save applies the choices per profile. The button then reads "Going (2)".
4. The button toggles going; the event page has the same per-profile controls.

## Home page

See `design.md` for the visual spec. Content:

- Small navbar: signed out shows Sign in and Sign up; signed in shows Profiles (n) and Sign out.
- Hero: "Pre-Flight" and "Should I go or should I stay at home?", one line saying what the app is (the only place the page names Pokémon).
- **Up next** (the pairings sheet): the next 5 upcoming or ongoing events by start time, ignoring filters.
- **Event list**: up to 25 upcoming and ongoing events, grouped by day, after filters. The count line says "12 of 29 events" or "First 25 of 31".
- Filters, all multi-select and combined with AND between groups: type (Casual, Prerelease, Challenge, Cup), host shop (only shops with upcoming events, with their counts), date range (Any time, This week, This weekend, Next 7 days, Next 30 days, Pick dates with From and To). The date filter applies before the 25 cap. Filters live in the URL query string so a filtered view can be shared.
- Card: date and start time, tournament ID, type with its icon, shop name and city, total going count, quick "I'm going" button. "Happening now" replaces the start time for ongoing events.
- Empty state: "Nothing matches these filters" with Clear filters.
- Disclaimer line under the list and the non-affiliation footer.
- Later, not v1: city or region filter.

## Event page

Shareable URL (`/events/{id}-{slug}`). Shows: type, title, shop, address, date and time in Lisbon time, tournament ID, link to the official listing, counts (going, confirmed), the viewer's own answers with "I'm going" / not going per profile, follow with a threshold ("tell me when 16 are going"), "Report a change or cancellation", the headcount disclaimer. Open Graph tags so shared links preview with the count. Past events show "This event has ended" and no buttons. A shared link with `?going` opens a "Going to …?" pop-up on an upcoming event; only tapping its button marks going (a link alone never does).

## Adding a missing event

A signed-in player pastes the official Event Locator link of a tournament and fills in type, date, start time, shop name and city. The app extracts the official Guid from the link when it can. If an event with the same Guid or link already exists, the player is sent to it. Otherwise it is saved as `pending` and an admin approves, edits or rejects it. The submitter gets an email when it is approved.

## Notifications (email)

- **Day-before reminder**: the day before the event, each account with a going answer gets one email listing its profiles: "Still going?" with Yes and No buttons (signed links, no login needed). Yes sets confirmed; No removes the going answer.
- **Event changed**: when the sync changes an event's date, time or shop, going answers become unconfirmed and the same "still going?" email goes out, saying what changed.
- **Possibly cancelled**: when the sync flags an event, followers and going players get one email suggesting they check with the shop.
- **Threshold**: a follower is emailed once when the going count first reaches their number. Optionally also when it drops back below (setting on the follow).
- **Moderation**: submitter is told when their event is approved or rejected.
- Reminders can be turned off, with a one-click unsubscribe link in each one. Threshold emails stop when the player unfollows the event. Moderation emails are a one-off reply to the submitter. "Event changed" and "possibly cancelled" emails are always sent: they are rare and matter to anyone planning to go.

## Privacy and brand

- Data kept: account email and name, linked logins, profile name, Player ID, birth year, answers, follows.
- Past answers are anonymised 90 days after the event (the profile link is removed, counts stay).
- Account deletion removes the account, its profiles and answers.
- Privacy policy and terms pages (English), a contact address for data requests.
- Not affiliated notice on every page: "Independent fan project. Not affiliated with, endorsed or sponsored by Nintendo or The Pokémon Company."
- Trademark names (Pokémon, Nintendo, Play! Pokémon and so on) appear on pages only where strictly needed: once in the hero line and in the notice.
- No official artwork or logos, except the type icons, which use the official-looking ball designs (Bruno's decision).

## Out of scope for v1

Payments, Pokémon account login, pairings and results, deck lists, native apps, capacity and waitlists, shop accounts, real-time websockets, Portuguese translation.

## Later phase: shop tools (not now)

A shop claims its events by email magic link, sees going players' names and Player IDs only for players who agreed to share with that shop, confirms attendance with players, and exports a list for typing into TOM (TOM has no import). Tables added then: shops, shop_user, claimed_by_shop_id on events, per-shop consent records. Nothing in v1 should block this, but nothing in v1 builds it.
