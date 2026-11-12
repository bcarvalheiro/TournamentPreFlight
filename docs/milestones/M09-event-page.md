# M09 · Event page

**Branch**: `m09-event-page` · **Read**: `product.md` (Event page, Attendance), `design.md` (Event page), `architecture.md` (Counts, Privacy enforcement)

## Goal
A shareable page per event with the division split and full answer controls.

## Scope
- Route `/events/{event}-{slug}` (slug optional; redirect to canonical). 404 for pending/hidden events except for admins and the submitter.
- `EventPageData`: event details, official URL, going by division, maybe count, confirmed count, possibly cancelled flag, ended flag.
- Per-profile controls: I'm going / Maybe / clear (uses `SetAttendance`).
- Follow form (threshold number, notify on drop) storing `event_follows`; unfollow.
- "Report a change or cancellation" form storing `event_reports` (signed in, rate limited).
- Open Graph and Twitter meta: title, "12 trainers going", no personal data.
- Optional 60s poll for counts while the page is visible.
- Add the event page to the privacy test dataset.

## Acceptance criteria
1. Division split is correct for a mix of birth years, using the season of the event date (test an event in a different season from today).
2. Shows "12 going (8 confirmed) · 4 maybe" correctly.
3. Ended event: shows "This event has ended", no controls, answers rejected.
4. Pending event: 404 for guests and other users; visible to the submitter and admins.
5. Follow create/update/delete works; one follow per user per event.
6. OG tags present with the count and no personal data.
7. Privacy test passes for the event page as guest and as another user.
