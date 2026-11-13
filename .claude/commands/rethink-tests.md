---
description: After implementation, re-examine the milestone's tests with Bruno - what they protect, what is missing
---

Rethink the tests for the current milestone. This is a teaching conversation with Bruno, who is studying the tests. Go slowly and do not change any test until he agrees.

1. List every test added or changed on this branch (`git diff main --stat -- tests/`).
2. For each test, explain in plain words:
   - **What it protects**: the rule or behaviour, and which acceptance criterion or `CLAUDE.md` rule it maps to.
   - **How it works**: walk through the code line by line where it helps (setup, action, assertion). Name the Pest/Laravel features it uses and why.
   - **How it could fail**: what change in the app would turn it red. If it could never realistically fail, say so.
   - **Weaknesses**: too weak (passes even when the feature is broken), too brittle (breaks on harmless changes like copy or markup), slow, or testing implementation instead of behaviour.
3. Then look for gaps:
   - acceptance criteria covered only by a manual check,
   - failure paths and edge cases (empty, invalid, unauthorised, boundary dates),
   - `CLAUDE.md` rules the milestone touches (privacy, under-13s, division, UTC, one answer per profile),
   - tests that are redundant and could go.
4. Propose a short list: tests to add, change or remove, each with a one-line reason. Mark your recommendation.
5. Wait for Bruno's decision. Apply only what he agrees to, explain each new test the same way before running it, then run `/verify`.
6. Note the outcome (what was added, changed, rejected and why) so `/wrap-up` can put it in the session log.
