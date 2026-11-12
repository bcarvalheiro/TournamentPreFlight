# M07 · Player profiles

**Branch**: `m07-profiles` · **Read**: `product.md` (Accounts and profiles), `architecture.md` (player_profiles, seasons), `design.md` (Profiles, ProfilePicker)

## Goal
An account manages its own profile and its children's.

## Scope
- Pages: profile list, create, edit, delete. First-run flow "Create your player profile" after first sign-in (keeps the pending intent).
- `CreatePlayerProfile` / `UpdatePlayerProfile` actions with validation: name required, Player ID digits only 1–10 and unique, birth year between 1920 and the current year.
- Child profiles: consent checkbox appears when the birth year means under 13 this year; consent time and user stored.
- Show the current division on each profile (season containing today).
- Navbar "Profiles (n)".
- `PlayerProfilePolicy`: only the owner can view, edit, delete.
- Own-data shapes in `app/Data/Own/`.

## Out of scope
Answers (M08). Admin editing of profiles.

## Acceptance criteria
1. A user can create, edit and delete their own profiles; another user gets 403 on each.
2. Duplicate Player ID is rejected with a clear message (also across accounts).
3. Under-13 profile without consent is rejected; with consent it saves `consent_given_at` and `consent_by_user_id`.
4. Division shown matches DivisionResolver for today.
5. First sign-in with no profiles lands on the create page and, after saving, continues to the pending intent's return URL.
6. Deleting a profile deletes its attendances.
