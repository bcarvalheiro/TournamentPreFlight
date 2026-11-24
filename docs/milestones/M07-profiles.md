# M07 · Player profiles

**Branch**: `m07-profiles` · **Read**: `product.md` (Accounts and profiles), `architecture.md` (player_profiles), `design.md` (Profiles, ProfilePicker)

## Goal
An account manages its own profile and its children's.

## Scope
- Pages: profile list, create, edit; delete from the list with a confirmation. "Add a trainer" is reached from the gate (with a pending intent) or from the notice every signed-in account without a profile sees (Bruno, 23 Nov: sign-in still lands on `/`).
- `CreatePlayerProfile` / `UpdatePlayerProfile` actions with validation: name required, Pokemon Player ID (POP ID) digits only 1–10 and unique, birth year between 1920 and the current year.
- Child profiles: consent checkbox appears when the birth year means the person turns 13 or younger this year (same rule as the model's `is_child`); consent time and user stored.
- Save profiles through the model (`create`/`update` on a `PlayerProfile`), never with bulk queries: the model's saving hook sets `is_child` and enforces consent (`decisions.md`, M04 review).
- Saving the first profile redirects with `redirect()->intended(route('home'))`: the M06 gate saved the page it blocked, so a player who pressed "I'm going" lands back on that page and receives the intent.
- A social account needs no password (M06 review); the first-profile page asks only for the profile.
- Keep M06's "Not now" on the first-profile page when the player arrived from "I'm going" (`signingInToGo`): it forgets the intent and returns to the page.
- The first-profile page asks "I am 13 or older" again when the account was taken over from someone who registered its email without proving it (M06 review: their `age_confirmed_at` is not the owner's answer).
- Limits per account (Bruno, 24 Nov): one profile aged 13 or older (the holder's own) and at most 3 child profiles; more children by email to support.
- Navbar for a signed-in account: "Trainers (n)", "Settings" and "Sign out" (Bruno, 24 Nov: profiles are "trainers" in the UI) (today it has only "Sign out", so profiles and Settings → Security cannot be reached).
- `PlayerProfilePolicy`: only the owner can view, edit, delete.
- Own-data shapes in `app/Data/Private/` (beside `app/Data/Public/`).

## Out of scope
Answers (M08). Admin editing of profiles.

## Acceptance criteria
1. A user can create, edit and delete their own profiles; another user gets 403 on each.
2. Duplicate Pokemon Player ID (POP ID) is rejected with a clear message (also across accounts).
3. Under-13 profile without consent is rejected; with consent it saves `consent_given_at` and `consent_by_user_id`.
4. Signing in without a profile lands on `/` (or the intended page), which shows a notice linking to "Add a trainer"; after "I'm going" the gate leads to the create page, and saving continues to the page it blocked, which receives the pending intent.
5. A social account without a password saves its first profile and uses the site; it can add a password later in Settings → Security (M06).
6. Deleting a profile deletes its attendances.
7. An account cannot add a second profile aged 13 or older, nor a fourth child; each refusal says why, and the fourth child's message points to support (Bruno, 24 Nov).
