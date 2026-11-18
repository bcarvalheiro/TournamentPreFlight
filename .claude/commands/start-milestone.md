---
description: Start a milestone - read context, create the branch, propose the slice list (no code)
argument-hint: <milestone id, e.g. M05>
---

Start milestone $ARGUMENTS.

1. Read `docs/progress.md`, then the milestone file matching `docs/milestones/$ARGUMENTS-*.md`, then only the docs it lists under **Read**.
2. Check `git status` is clean and you are on an up-to-date `main`. If not, stop and tell me.
3. Create the branch named in the milestone file.
4. Set the milestone to "in progress" in `docs/progress.md`.
5. Propose a plan, and **do not write code yet**:
   - an ordered **slice list**. For each slice: a one-line goal, the files, the test name, and the doc page it follows. Every slice delivers behaviour a test can check; set-up with nothing to test (a config value, a migration) goes into the first slice that needs it,
   - which acceptance criteria each slice covers; every criterion is covered by at least one slice,
   - any decision the docs do not settle, with your recommendation,
   - anything in the milestone that conflicts with another doc,
   - anything you need from me (credentials, a choice, a package to approve).
6. Wait for my approval. Then write the slice list into `docs/progress.md` under **Current** as a **Plan** checklist, one `- [ ]` line per slice (`Slice 1: goal (test name)`). When the plan changes later, edit the line and add a short reason.
7. Stop. Remind me of the `watch` command, suggest `git add docs/progress.md` and a commit message for the plan, and tell me to run `/slice` for the first slice. Do not write tests or code in this command.
