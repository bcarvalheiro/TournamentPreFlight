# M06 · Sign in: email and password, Google, Discord, X

**Branch**: `m06-auth` · **Read**: `architecture.md` (Auth flows, Data model: users, social_accounts), `product.md` (Accounts and profiles), `design.md` (Sign in)

## Goal
Players register with email and password, or sign in with Google, Discord or X. Only players with a player profile can use the site signed in.

## Scope
- Keep the starter kit's Fortify auth (login, registration, email verification, password reset, confirm password) and restyle its pages. Remove two-factor auth, passkeys, the dashboard and Settings → Appearance (routes, pages, components, tests, Fortify features). Drop the two-factor columns, the `passkeys` table and the `magic_login_tokens` table with a new migration. Signed-in players land on `/`.
- Age gate: an account is created only after the person ticks "I am 13 or older"; the time is stored in `users.age_confirmed_at` (new nullable column). Applies to registration and to social sign-up alike. Under-13s only exist as child profiles on a parent's account (M07).
- Social sign-in with Socialite: Google and X are built-in drivers; Discord comes from `socialiteproviders/discord` with `socialiteproviders/manager` (approved). Generic routes `/auth/{provider}/redirect` and `/auth/{provider}/callback`, restricted with `whereIn` to google, discord and x. The shared logic lives in one class used by every driver. Linking rules from `architecture.md`.
- Social sign-up: a new identity goes to a welcome page (name prefilled from the provider, the provider's email read-only, the age box); an identity without an email the provider certifies for is sent to register with email and password instead. `users.name` stays required. `users.password` becomes nullable: social accounts need no password and may add one in Settings → Security (changed in review, 23 Nov).
- Player gate (middleware): anyone, signed in or not, may look at every page; only a player answers: a verified email and a player profile (its Player ID is required); a password is optional. The gate steps in only on the way to answering, when the session holds a pending "I'm going": a user who is not ready is redirected to the verify notice, then to the placeholder "create your profile" route that M07 builds (`redirect()->guest()` keeps the page). Auth pages, settings, logout, `events.going` and `profiles.*` stay open. It decides by route name, so every route it leaves open has one (the kit's `/settings` redirect is named `settings`). Guests are not affected. (Changed in review, 23 Nov: it used to redirect from every page.)
- Pending intent: a guest pressing "I'm going" (a POST form; upcoming events only) has the event stored in the session (never in the URL) and the page kept as the intended URL and is sent to sign in; after sign-in, and after the gate passes, they return to that page and it receives the intent once as a prop. Acting on it is M08.
- Sign-in UI styled per `design.md`, with Google, Discord and X buttons; a provider without credentials is hidden. Apple waits for M13.

## Out of scope
Profiles (M07), acting on the intent (M08), Apple (M13).

## Acceptance criteria
1. Registering with name, email, password and the 13+ box creates the user with `age_confirmed_at` set and sends a verification email; without the box no account is created.
2. A signed-in user with an unverified email or no player profile can browse every page; after pressing "I'm going" they are redirected to the verify notice, then the profile placeholder (auth, settings and logout stay open). A password is optional. (Changed in review.)
3. Google, Discord and X (Socialite faked): a new identity creates an account only after the welcome page's age box; a known identity signs in; an unknown identity whose provider-verified email matches an existing user links to that user; an unverified provider email never links.
4. No two-factor or passkey routes, columns or table remain, and no `magic_login_tokens` table.
5. Pressing "I'm going" while signed out returns the player to the same URL after sign-in, with the intent handed to the page.
6. Login is rate limited and sign out clears the session.
