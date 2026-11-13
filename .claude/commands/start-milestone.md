---
description: Start a milestone - read context, create the branch, present a plan (no code yet)
argument-hint: <milestone id, e.g. M05>
---

Start milestone $ARGUMENTS.

1. Read `docs/progress.md`, then the milestone file matching `docs/milestones/$ARGUMENTS-*.md`, then only the docs it lists under **Read**.
2. Check `git status` is clean and you are on an up-to-date `main`. If not, stop and tell me.
3. Create the branch named in the milestone file.
4. Set the milestone to "in progress" in `docs/progress.md`.
5. Present a plan, and **do not write code yet**:
   - the files you will create or change,
   - the list of tests you will write, one per acceptance criterion (name each test),
   - any decision the docs do not settle, with your recommendation,
   - anything in the milestone that conflicts with another doc,
   - anything you need from me (credentials, a choice, a package to approve).
6. Wait for my approval. After I approve: write the tests first. For each test, explain in plain words what it protects, how it works and what failure we expect, then run it and show the failure. Then implement. After `/verify` passes, remind me to run `/rethink-tests`.
