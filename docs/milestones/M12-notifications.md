# M12 · Email notifications

**Branch**: `m12-notifications` · **Read**: `product.md` (Notifications), `architecture.md` (Notifications), `design.md` (Copy rules)

## Goal
Counts people can trust: reminders, change alerts, thresholds.

## Scope
- Markdown mail theme in our tokens (graphite header, red buttons), non-affiliation line in the footer.
- `StillGoingNotification` (reasons: day_before, changed) with signed Yes/No links per profile; Yes sets `confirmed_at`, No removes the going answer; landing page confirms the result without login.
- `reminders:day-before` daily at 18:00 Lisbon for events starting tomorrow; one mail per account, not per profile; never twice for the same event.
- `CheckThresholds` job: notify each follower once when going first reaches `notify_at_count`; if `notify_on_drop`, once when it falls back below; reset rules documented in code.
- `PossiblyCancelled` and `SubmissionReviewed` mails (replace stubs).
- Notification settings page (four toggles) and signed one-click unsubscribe per category.
- `attendances:anonymise` daily job (90 days after event end).

## Acceptance criteria
1. Day-before: an account with two going profiles gets one mail listing both; running the command twice sends nothing more.
2. Signed Yes confirms, No removes; a tampered or expired link fails; links work signed out.
3. Changed event: confirmations cleared and one mail per affected account saying what changed.
4. Threshold: reaching 16 notifies once; going 16→15→16 does not re-notify unless notify_on_drop logic says so (test both settings).
5. Unsubscribe and settings stop the matching category only.
6. Anonymisation nulls the profile link after 90 days and counts stay the same.
7. Every mail rendered in a test contains no other player's name.
