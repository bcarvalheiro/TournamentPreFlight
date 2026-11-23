# M09 · Event page

**Branch**: `m09-event-page` · **Read**: `product.md` (Event page, Attendance), `design.md` (Event page), `architecture.md` (Counts, Privacy enforcement)

## Goal
A shareable page per event with the counts and full answer controls.

## Scope
- Route `/events/{event}-{slug}` (slug optional; redirect to canonical). 404 for pending/hidden events except for admins and the submitter.
- `EventPageData`: event details, official URL, going count, confirmed count, possibly cancelled flag, ended flag.
- Per-profile controls: I'm going / not going (uses `SetAttendance`).
- Follow form (threshold number, notify on drop) storing `event_follows`; unfollow.
- "Report a change or cancellation" form storing `event_reports` (signed in, rate limited).
- Open Graph and Twitter meta: title, "12 trainers going", no personal data.
- **Share link** (decided in M06 review): `?going` on an upcoming event's page opens the "Going to …?" pop-up with an "I'm going" button and the headcount line; a "Share" button copies that link. Opening the link changes nothing; only the tap marks going (M08 flow, so guests go through the pending intent). Hidden, cancelled and ended events show no pop-up.
- Optional 60s poll for counts while the page is visible.
- Add the event page to the privacy test dataset.

## Acceptance criteria
1. Shows "12 going (8 confirmed)" correctly.
2. Ended event: shows "This event has ended", no controls, answers rejected.
3. Pending event: 404 for guests and other users; visible to the submitter and admins.
4. Follow create/update/delete works; one follow per user per event.
5. OG tags present with the count and no personal data.
6. Privacy test passes for the event page as guest and as another user.
7. Opening `/events/{id}-{slug}?going` shows the pop-up for an upcoming event and creates no attendance and no pending intent; an ended or cancelled event shows no pop-up.
