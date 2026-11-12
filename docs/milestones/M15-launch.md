# M15 · Launch hardening

**Branch**: `m15-launch` · **Read**: `architecture.md` (Environments, Security basics), `decisions.md` (open questions: hosting, email)

## Goal
Ready for real players.

## Scope
- Requires Bruno's decisions on hosting and email provider. If missing, stop and ask.
- Production config: queue worker, scheduler cron, mail provider, HTTPS, trusted proxies, `APP_DEBUG=false`, log channel, backups of MySQL.
- Error pages (404, 419, 500, 503) in our design.
- Rate limits reviewed; security headers; `composer audit` and `npm audit` clean or documented.
- Performance: query count on home and event page (no N+1, add a test using `DB::listen` or Laravel's lazy-loading prevention in non-production), indexes checked with EXPLAIN.
- Accessibility pass with the `ui-reviewer` agent on home, event, sign-in, profiles: keyboard path, focus, labels, contrast.
- SEO: titles, descriptions, sitemap of upcoming events, robots.
- Deploy script or instructions in `README.md`; first real `events:sync` run on production with `--dry-run` first.

## Acceptance criteria
1. `Model::preventLazyLoading()` on outside production and the full suite passes.
2. Home page renders with a bounded number of queries regardless of event count (test with 25 and 100 events).
3. Error pages render in our layout.
4. Deploy checklist in README completed once on staging.
