# M06 · Sign in: magic link and Google

**Branch**: `m06-auth` · **Read**: `architecture.md` (Auth flows, Data model: users, social_accounts, magic_login_tokens), `product.md` (Accounts and profiles), `design.md` (Sign in)

## Goal
Players sign in with an email magic link or Google. Passwords are gone.

## Scope
- Remove password login, registration, reset and confirm from the starter kit (routes, pages, Fortify features, tests). Drop the password column with a new migration.
- Magic link: request page/sheet, `MagicLinkMail`, token stored hashed, 15-minute single use, rate limited, creates the account on first use.
- Google via Socialite: redirect, callback, linking rules from `architecture.md`.
- Sign-in UI: one sheet (from navbar and from quick buttons) and a full page fallback at `/login`, styled per `design.md`. Apple and Discord buttons are hidden until M13.
- After first sign-in with no profiles, redirect to a placeholder "create your profile" route (M07 builds it).
- Pending-intent plumbing: `return_to` and `intent` kept through the sign-in round trip (session), returned to the page as a prop. Acting on it is M08.

## Out of scope
Profiles (M07), Apple and Discord (M13).

## Acceptance criteria
1. Requesting a link for a new email sends one mail; clicking it signs in and creates the user with verified email.
2. A used or expired link fails with a friendly message; the 6th request in an hour is rate limited.
3. Google callback: new identity creates a user; known identity signs in; unknown identity with an existing verified email links to that user. (Mock Socialite.)
4. No password routes remain (`/register`, `/forgot-password`, `/reset-password`) and no password column exists.
5. Signing in from a quick button returns to the same URL with the intent prop.
6. Sign out works and clears the session.
