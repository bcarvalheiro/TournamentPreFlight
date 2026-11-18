---
description: Fresh-context review of the branch diff with the reviewer subagents
---

Review the current branch against `main`.

1. Run `git diff main...HEAD --stat` to see what changed.
2. Use the `code-reviewer` subagent on the diff and the current milestone file.
3. If the diff touches controllers, routes, Inertia props, `app/Data`, mails, notifications or logs, also use the `privacy-guard` subagent.
4. If the diff touches `resources/js` or `resources/css`, also use the `ui-reviewer` subagent on the affected pages.
5. Give me one merged list of findings, most serious first, each marked must-fix or optional, with your opinion on each.
6. Fix the items I agree to, one logical fix at a time. After each, show `git diff --stat`, explain the change, and suggest `git add <paths>` and a commit message; wait until I have committed before the next fix. Run `/verify` after the last one.
