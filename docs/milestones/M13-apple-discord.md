# M13 · Apple and Discord login

**Branch**: `m13-apple-discord` · **Read**: `architecture.md` (Auth flows)

## Goal
The two remaining sign-in methods.

## Scope
- Install `socialiteproviders/manager`, `socialiteproviders/apple`, `socialiteproviders/discord` (named in the plan, so allowed). Event listener registration per their docs for Laravel 13.
- Apple: handle the POST callback (CSRF exception for that route only), store name on first login, private relay emails are fine.
- Discord: scope `identify email`; refuse sign-in if Discord gives no verified email, with a friendly message.
- Show the buttons in the sign-in sheet; config-driven so a provider without credentials is hidden.
- `.env.example` entries and a short setup note in `README.md` for creating the Apple and Discord apps.

## Acceptance criteria
1. Each provider: new, returning and email-link cases (mocked), same as Google in M06.
2. Discord without verified email is refused.
3. A provider with missing config is not shown and its routes 404.
