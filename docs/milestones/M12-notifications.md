# M12 · Email notifications

**Branch**: `m12-notifications` · **Read**: `product.md` (Notifications), `architecture.md` (Notifications), `design.md` (Copy rules)

## Goal
Counts people can trust: the day-before reminder with the headcount, and change alerts. No follows and no thresholds (decisions.md, 25 Nov).

## Scope
- Markdown mail theme in our tokens (graphite header, red buttons), non-affiliation line in the footer.
- `StillGoingNotification` (reasons: day_before, changed) with how many trainers are going and signed Yes/No links per profile; Yes sets `confirmed_at`, No removes the going answer; landing page confirms the result without login.
- `reminders:day-before` daily at 18:00 Lisbon for events starting tomorrow; one mail per account, not per profile; never twice for the same event.
- `PossiblyCancelled` and `SubmissionReviewed` mails (replace stubs).
- Notification settings page (one toggle: reminders) and a signed one-click unsubscribe in reminder emails. "Event changed" and "possibly cancelled" are always sent, whatever the setting.
- `attendances:anonymise` daily job (90 days after event end).

## Acceptance criteria
1. Day-before: an account with two going profiles gets one mail listing both and saying how many trainers are going; running the command twice sends nothing more.
2. Signed Yes confirms, No removes; a tampered or expired link fails; links work signed out.
3. Changed event: confirmations cleared and one mail per affected account saying what changed.
4. Turning reminders off (settings or unsubscribe link) stops reminder emails and nothing else.
5. Anonymisation nulls the profile link after 90 days and counts stay the same.
6. Every mail rendered in a test contains no other player's name.
