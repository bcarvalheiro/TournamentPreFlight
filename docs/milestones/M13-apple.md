# M13 · Apple login

**Branch**: `m13-apple` · **Read**: `architecture.md` (Auth flows)

## Goal
The remaining sign-in method. (Discord moved to M06, Bruno, 21 Nov.)

## Scope
- Install `socialiteproviders/apple` (named in the plan, so allowed; `socialiteproviders/manager` is already installed in M06). Event listener registration per its docs for Laravel 13.
- Add `apple` to the generic `/auth/{provider}` routes and the shared social sign-in class from M06.
- Apple: handle the POST callback (CSRF exception for that route only), store name on first login, private relay emails are fine. Needs the paid Apple Developer Program; the client secret is a JWT that expires at most every 6 months.
- Show the button in the sign-in UI; config-driven so a provider without credentials is hidden.
- `.env.example` entries and a short setup note in `README.md` for creating the Apple app.

## Acceptance criteria
1. New, returning and email-link cases (mocked), same as Google in M06.
2. Apple with missing config is not shown and its routes 404.
