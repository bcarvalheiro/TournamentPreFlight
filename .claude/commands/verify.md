---
description: Run the full quality gate and fix what fails
---

Run `composer verify`.

If anything fails, fix the cause and run it again until it passes. Rules:
- Never skip, delete or weaken a test, and never lower the Larastan level, to get green.
- If a test fails because the spec changed on purpose, update the test and say why.
- If a failure is outside this milestone and not caused by it, report it and add it to "Found along the way" in `docs/progress.md`.

End with a one-line result: passed, or what is still failing.
