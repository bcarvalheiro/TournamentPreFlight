# How we build: the agentic workflow

This is the working method for building Pre-Flight with Claude Code. It is written for both Bruno and Claude.

## The idea in one paragraph

Each milestone is small enough to finish in one Claude Code session, and it lives on its own branch. A session starts clean, reads only what it needs, plans before coding, writes tests from the acceptance criteria first, and does not finish until `composer verify` passes and a fresh-eyes review has run. Tests are the contract between sessions: whatever a past session built is protected by its tests, so a later session cannot quietly break it. Progress and decisions are written to files, not left in chat, because the next session starts with no memory.

## The loop for every milestone

```
/clear  →  /start-milestone M05  →  plan (approve)  →  tests first  →  implement
        →  /verify  →  /rethink-tests  →  /review  →  Bruno checks in browser  →  /wrap-up  →  merge
```

1. **Start clean.** Run `/clear` (or open a new session). Old context costs tokens and causes drift.
2. **`/start-milestone M05`.** Claude reads `CLAUDE.md`, `docs/progress.md` and the milestone file, creates the branch `m05-home-page`, and presents a plan. It writes no code yet.
3. **Approve the plan.** Bruno reads it. Push back now: a wrong plan is cheap to fix, wrong code is not. Using plan mode (Shift+Tab until "plan mode") for this step is a good habit.
4. **Tests first.** Claude turns each acceptance criterion into a Pest test and runs them to watch them fail. This proves the tests test something. Before running each test, Claude explains it in plain words: what it protects, how it works, and what failure we expect to see. Slow down here; Bruno is studying the tests.
5. **Implement** until the tests pass. Commit in small steps.
6. **`/verify`.** Runs the full gate and fixes failures. Never skip a test to get green.
6b. **`/rethink-tests`.** Now that the code exists, Claude and Bruno look at the tests again, one by one: does each still make sense, does it test behaviour rather than implementation, is it too weak or too brittle, and which tests are missing (edge cases, failure paths, rules from `CLAUDE.md` the milestone touches). Claude proposes; Bruno decides. Agreed changes are made and `/verify` runs again. This is a conversation, not a checklist to rush.
7. **`/review`.** Starts the `code-reviewer` subagent (and `privacy-guard` when public data changed, `ui-reviewer` when UI changed) with fresh context on the branch diff. Claude fixes what is agreed.
8. **Bruno checks it in the browser**, on a phone-sized window too. Anything wrong goes back to step 5.
9. **`/wrap-up`.** Claude updates `docs/progress.md` and `docs/decisions.md`, commits, and writes the pull request text.
10. **Merge** to `main` (PR on GitHub, CI green). Then `/clear` and start the next one.

If a session runs long, finish a coherent slice, `/wrap-up` with the milestone marked "in progress" and notes on what is left, and continue in a new session. Prefer that over `/compact`, because the files carry state better than a summary.

## Sequencing

Milestones are ordered so each one stands on tested ground:

1. **Foundation** (M01–M04): scaffold, tooling and CI; design tokens and layout shell; a spike that records what pokedata.ovh really returns; the domain schema with seed data.
2. **Visible product on fake data** (M05): the home page, built against seeded data, so Bruno sees the real UI early.
3. **The core loop** (M06–M09): login, profiles, "I'm going", event page.
4. **Real data** (M10–M11): nightly import and player-submitted events with moderation.
5. **Trust and reach** (M12–M14): notifications, Apple and Discord login, privacy and legal.
6. **Launch** (M15).

The full list with acceptance criteria is in `docs/milestones/`. Status is in `docs/progress.md`.

Why this order: the importer depends on real response fields we have not seen yet, so a cheap spike (M03) comes early and the importer comes after the core loop works on seeded data. The home page comes before login because it is the thing Bruno can react to, and its design will shape the components everything else reuses.

## Testing is the guardrail

- **Pest feature tests** are the main safety net. Every acceptance criterion in a milestone becomes at least one test.
- **Unit tests** for pure logic: division from birth year and season, the sync diff, date-range presets.
- **Architecture tests** (Pest `arch()`): public controllers return only `app/Data/Public` objects; no `dd`/`dump`; models are not used in Inertia props directly.
- **The privacy test**: seeds profiles with distinctive names and Player IDs, hits every public route as a guest and as another user, and asserts none of those strings appear in the response. Every new public route is added to its dataset.
- **Browser tests** (Pest browser plugin, which runs Playwright) for the few flows that only work in a real browser: the quick "I'm going" button signed out, the profile picker, filters on a phone viewport. Keep them few; they are slow.
- **Fixtures, not live calls.** The importer is tested against recorded pokedata.ovh responses in `tests/Fixtures/pokedata/`. Tests never hit the network (`Http::preventStrayRequests()`).
- **Larastan** at level 7 (the kit default), raised when the code is clean. Never lowered.

A test that fails because the spec changed is updated in the same commit as the spec change, with the reason in the commit message.

## When to use subagents

Subagents get a fresh, separate context. Use them where fresh eyes or a narrow job helps, not to split one feature across several agents.

| Use a subagent for | Why |
| --- | --- |
| Reviewing a finished diff (`code-reviewer`, `privacy-guard`) | The implementer is biased by its own reasoning; a fresh context catches what it missed |
| Checking UI against the design spec (`ui-reviewer`) | Screenshots and comparison are noisy; keep them out of the main context |
| Researching a package or API before planning (built-in Explore agent) | Keeps doc dumps out of the main context; you only need the conclusion |

Do not use subagents to implement parts of the same milestone in parallel. They cannot see each other's choices and the pieces will not fit.

**Parallel work, later.** Once M09 is merged, some milestones are independent (M11 moderation, M13 Apple/Discord, M14 legal pages). If Bruno wants speed, run them in separate git worktrees (`git worktree add ../preflight-m13 -b m13-apple-discord`), one Claude session each, merged one at a time with `composer verify` after each merge. Sequential is the default.

## Hooks, commands and settings in this kit

- `.claude/settings.json`: allows the safe, frequent commands (tests, Pint, Larastan, npm scripts, read-only git) so Claude is not asking every minute; asks before installing packages or pushing; denies reading `.env` and force-pushing.
- **PostToolUse hook** `format.sh`: formats every PHP file Claude edits with Pint and every TS/TSX/CSS file with `vp fmt` (Oxfmt, from the kit), so style never shows up in reviews.
- **PreToolUse hook** `protect-files.sh`: blocks edits to `.env*`, `vendor/`, `node_modules/`, lock files and already-merged migrations. A merged migration is changed with a new migration.
- **Commands**: `/start-milestone`, `/verify`, `/rethink-tests`, `/review`, `/ui-check`, `/wrap-up`.
- **Subagents**: `code-reviewer`, `privacy-guard`, `ui-reviewer`.

## Rules for Claude during a session

- Read only what the milestone needs. The docs are split so you do not have to load all of them.
- If the milestone file and another doc disagree, stop and ask.
- If something outside the milestone is broken, note it under "Found along the way" in `docs/progress.md`. Fix it only if it blocks the milestone.
- If you are about to guess an API, look it up first.
- Show the failing test before the fix when fixing a bug.
- Never weaken a test, skip it, or lower the Larastan level to get green.
- Never edit a migration that is already on `main`.

## Rules for Bruno

- Approve plans before code. It is the highest-leverage minute of the session.
- Look at the result in a browser at phone width before `/wrap-up`.
- Keep one milestone per session and per branch. If you want something extra, add it to `docs/progress.md` for a later milestone.
- When Claude makes the same mistake twice, add one line to `CLAUDE.md` so no future session makes it.
