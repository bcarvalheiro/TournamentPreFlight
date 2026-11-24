# M08 · "I'm going"

**Branch**: `m08-attendance` · **Read**: `product.md` (Attendance, quick button), `architecture.md` (Counts, Privacy enforcement, Auth flows: pending intent), `design.md` (Event list item, ProfilePicker)

## Goal
The core loop: players answer for each of their profiles and the counts update.

## Scope
- `SetAttendance` action: (profile, event, going: bool). Going creates the row if missing (unique key); not going deletes it. Rejects events that are not listed or have ended, and profiles the user does not own.
- Routes: `PUT /events/{event}/attendance` taking `{profiles: {id: bool}}` for several profiles at once.
    - Question from Bruno: id: bool? is right? 
- Quick button behaviour from `product.md`: one profile toggles going; several opens `ProfilePicker`; signed out, or not a player yet (no verified email or Pokemon Player ID (POP ID)), uses the pending intent from M06/M07 (the same POST; M06's `GoingButton` posts it for everyone, M08 makes it toggle for players) (kept in the session, handed to the page as a prop) and resumes after sign-in.
- The "I'm going" button in M09's share pop-up (`?going`) uses this same flow: one profile toggles, several open the picker, signed out posts the pending intent (M06: a POST form, upcoming events only).
- After saving: toast, counts and button labels refresh (partial reload). "You: Ana, Tiago" line on slips for multi-profile accounts.
- Dispatch a `CheckThresholds` job after each change (the job can be a no-op until M12).
- Extend the privacy test: a signed-in user sees their own profile names on their own slips, but another user and a guest never see them.

## Out of scope
Confirmations (M12).

## Acceptance criteria
1. Going, going again, then not going on the same profile leaves 1, 1, 0 rows: never two rows.
2. Counts on the home page reflect the answers.
3. Cannot answer for someone else's profile (403), for a pending/hidden/cancelled event, or for an ended event (422).
4. Several profiles: picker saves different choices per profile in one request.
5. Browser test: signed out, tap "I'm going" on a slip, sign in with email and password, land back on the home page with the picker open (family account) or the answer applied (solo account).
6. Privacy test extended and passing.
