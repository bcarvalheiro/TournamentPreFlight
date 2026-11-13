# Pre-Flight dev kit

Everything Claude Code needs to build Pokémon Pre-Flight, plus the working method. Drop it into a freshly scaffolded Laravel app and start with milestone M01.

## What's in here

```
CLAUDE.md                     lean rules Claude reads every session
docs/
  workflow.md                 the agentic method: loop, testing, subagents, parallel work
  progress.md                 project state; Claude updates it every session
  product.md                  what the app does: rules, flows, home page, notifications
  architecture.md             stack, code layout, data model, import, auth, privacy enforcement
  design.md                   tokens, fonts, our type icons, home page anatomy, components
  decisions.md                decision log and open questions
  milestones/M01..M15         one file per session: scope, out of scope, acceptance criteria
design/prototype/             the chosen "Pre-Flight Pairings" prototype (open pairings.html)
.claude/
  settings.json               permissions + hooks
  hooks/format.sh             auto-format edited files (Pint, vp fmt)
  hooks/protect-files.sh      block edits to .env, vendor, lock files, merged migrations
  commands/                   /start-milestone /verify /rethink-tests /review /ui-check /wrap-up
  agents/                     code-reviewer, privacy-guard, ui-reviewer
```

## Setup (once, about 20 minutes)

1. **Install** PHP 8.4, Composer, Node 22+, MySQL 8.4 (Laravel Herd + DBngin is easiest on Mac/Windows), the Laravel installer (`composer global require laravel/installer`) and Claude Code.
2. **Scaffold the app**: `laravel new pre-flight`. Choose the **React** starter kit, Laravel's built-in authentication, **Pest**, and **MySQL**. If it offers **Laravel Boost**, say yes and pick Claude Code (it gives Claude version-correct Laravel docs).
3. **Create two databases**: `tournament_preflight` and `tournament_preflight_test`.
4. **Copy this kit into the app root**, including the hidden `.claude` folder:
   `cp -R dev-kit/. pre-flight/`
   If Boost already wrote a `CLAUDE.md`, keep ours at the top and its generated section below.
5. **Git**: `git init -b main && git add -A && git commit -m "Scaffold + dev kit"`. Create an empty GitHub repo and push `main`.
6. **Start**: `cd pre-flight && claude`, then type `/start-milestone M01`.

The hooks need `php` on your PATH (already true after step 1).

## Working on an existing clone

```
composer setup     installs PHP and JS packages, copies .env.example to .env,
                   generates APP_KEY, migrates, builds the frontend
composer verify    the full gate; must pass before any commit
composer dev       app, queue, logs and Vite together
```

Create the `tournament_preflight` and `tournament_preflight_test` databases first. Tests read `APP_KEY` from your `.env`, so `php artisan key:generate` (done by `composer setup`) is required before `composer verify`.

## How a session goes

```
/clear
/start-milestone M05     Claude reads context, creates the branch, shows a plan
(you approve or push back)
                         Claude writes failing tests, then the code
/verify                  full gate: Pint, Larastan, build, TypeScript, vp check, Pest
/rethink-tests           look at the tests again together, before review
/review                  fresh-context subagents review the diff
(you look at it in the browser, at phone width too)
/wrap-up                 progress + decisions updated, commit, PR text
```

Merge the PR when CI is green, then `/clear` and start the next milestone. One milestone per session and per branch.

Tips: press Shift+Tab to switch to plan mode for the planning step; press Esc to stop Claude mid-step; when Claude makes the same mistake twice, add a line to `CLAUDE.md`.

## The order of work

| Phase | Milestones | You can see |
| --- | --- | --- |
| Foundation | M01 scaffold and CI · M02 design shell · M03 pokedata spike · M04 schema and seed data | A styleguide page, real pokedata fields |
| Product on fake data | M05 home page | The Pairings home page in your browser |
| Core loop | M06 sign in · M07 profiles · M08 "I'm going" · M09 event page | The app working end to end on seed data |
| Real data | M10 nightly import · M11 submitted events and admin | Real Portuguese events |
| Trust and reach | M12 emails · M13 Apple and Discord · M14 privacy and legal | Launch-ready features |
| Launch | M15 hardening and deploy | Live |

Before M15 you need to decide hosting and the email provider (see `docs/decisions.md`). Before M10, send the pokedata.ovh author the questions M03 writes down.
