---
description: Run the next slice of the milestone - propose, wait, test red, code green, show the diff, suggest a commit
argument-hint: [optional: which slice, or a change to it]
---

Run one slice (see "In slices" in `CLAUDE.md`).

1. Read the **Plan** in `docs/progress.md` and take the first unticked slice (or the one in $ARGUMENTS).
2. Run `git status --short`. If there are uncommitted changes, stop and ask Bruno: the last slice may not be committed yet.

## Stop 1: proposal (no code yet)

Brief, in this shape:
- **Goal**: one sentence, and the acceptance criterion it moves forward.
- **Files**: each file created or changed.
- **Sketch**: signatures or the key snippet, 20 lines at most.
- **Standard way**: the doc page it follows, or the existing kit/app code it reuses. Flag any deviation.
- **New things**: every new class, interface, helper, folder, package or config key, and why this slice needs it now. "None" is the best answer.
- **Test**: name, what it protects, how it works, the failure you expect.
- **Size**: rough changed lines; if well over ~150, offer a split.

Then wait for Bruno. If he changes the slice, update its Plan line.

## Build

3. Write the test, run it alone, show the failure. If it fails for an unexpected reason, stop and explain.
4. Write the smallest code that makes it pass, as proposed. If you need something the proposal didn't name (a file, class, package, or another approach), stop and ask first.
5. Run the slice's tests. Run `composer verify` only if the slice touched shared config (composer.json, package.json, vite, phpunit, CI).

## Stop 2: show and suggest

- `git status --short` and `git diff --stat` (untracked files count too).
- Walk through the important hunks in plain words, ready to paste into documentation; Tailwind notes when UI changed.
- Anything that differs from the proposal, and why.
- Tick the slice in the Plan (`docs/progress.md` goes in the same commit).
- Suggest the exact `git add <paths>` and a commit message. Never run them.

Then wait. Do not start the next slice.
