---
description: End the session - update progress and decisions, suggest the commit, write the PR text
---

Wrap up this session.

1. Run `composer verify`. If it fails, stop and tell me; do not wrap up on red.
2. Update `docs/progress.md`:
   - milestone status (review if all acceptance criteria are met, otherwise in progress),
   - a session log entry at the top: date, outcome, the commits on this branch (`git log --oneline main..HEAD`), problems hit and how they were solved, the outcome of `/rethink-tests` and `/review`, what is left, anything surprising. No file-by-file list: the slice commits and their messages carry that,
   - "Found along the way" items you noted.
3. Add any new decisions to the log in `docs/decisions.md` and tick resolved open questions.
4. If a doc (`product.md`, `architecture.md`, `design.md`) is now wrong because of something we agreed, fix it.
5. If I corrected the same mistake twice this session, propose one line for `CLAUDE.md`.
6. Suggest the exact `git add <paths>` for the files this wrap-up changed, and a commit message. Do not run it; wait until I have committed.
7. Write the pull request title and body: what the milestone delivers, the acceptance criteria with how each is tested, the list of commits, screenshots to take, and anything I should check by hand. Then give me the `git push -u origin <branch>` and `gh pr create` commands to run myself.
