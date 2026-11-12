# M11 · Submitted events and admin moderation

**Branch**: `m11-submissions-admin` · **Read**: `product.md` (Adding a missing event, Users: admin), `architecture.md` (Admin, Event import matching)

## Goal
Players fill gaps in the import; an admin keeps the list clean.

## Scope
- "Add a missing event" page (signed in): official locator URL, type, date, start time, shop name, city. Extract the Guid from the URL when its format allows (document the patterns found). Duplicate by Guid or URL redirects to the existing event with a message.
- Saved as `pending`, source `player`, `added_by_user_id`. Rate limit 5 per day per user.
- Admin area (`can:admin`): pending queue (approve, edit then approve, reject with note), events search with hide/unhide/mark cancelled, reports list (mark handled), seasons edit, import runs (from M10).
- `SubmissionReviewed` notification (stub mail until M12 if needed).
- Admin navigation link visible only to admins.

## Acceptance criteria
1. Non-admins get 403 on every admin route (dataset test over all admin routes).
2. Submission with an existing Guid or URL creates nothing and redirects to the existing event.
3. Approve lists the event; reject keeps it hidden with the note; both notify the submitter.
4. Hidden events vanish from home and their page 404s for non-admins.
5. Editing a season changes divisions shown on the event page accordingly.
