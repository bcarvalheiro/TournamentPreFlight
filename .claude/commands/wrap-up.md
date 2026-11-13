---
description: End the session - update progress and decisions, commit, write the PR text
---

Wrap up this session.

1. Run `composer verify`. If it fails, stop and tell me; do not wrap up on red.
2. Update `docs/progress.md`:
   - milestone status (review if all acceptance criteria are met, otherwise in progress),
   - a session log entry at the top: date, what was done (file by file, written so it could be pasted into documentation), problems hit and how they were solved, the outcome of `/rethink-tests`, what is left, anything surprising,
   - "Found along the way" items you noted.
3. Add any new decisions to the log in `docs/decisions.md` and tick resolved open questions.
4. If a doc (`product.md`, `architecture.md`, `design.md`) is now wrong because of something we agreed, fix it.
5. If I corrected the same mistake twice this session, propose one line for `CLAUDE.md`.
6. Commit with a clear message.
7. Write the pull request title and body: what the milestone delivers, the acceptance criteria with how each is tested, screenshots to take, and anything I should check by hand. Ask before pushing.
