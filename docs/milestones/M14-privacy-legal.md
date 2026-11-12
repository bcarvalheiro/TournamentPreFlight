# M14 · Privacy, legal and account deletion

**Branch**: `m14-privacy-legal` · **Read**: `product.md` (Privacy and brand), `architecture.md` (Privacy enforcement)

## Goal
The trust basics before real users arrive.

## Scope
- Pages: Privacy policy, Terms, About (what the app is and is not), Contact (an email address from config). Write clear English drafts marked "Draft, needs review" at the top; Bruno will have them reviewed. Not legal advice.
- Account deletion (settings page): confirmation step, deletes account, social accounts, profiles, answers, follows; counts drop accordingly.
- "Download my data" as JSON (account, profiles, answers, follows).
- Cookie notice only if non-essential cookies exist (they should not).
- Audit: walk every public route, mail and log line for personal data; extend the privacy test dataset to every public route now existing.
- Non-affiliation notice confirmed on every page and mail.

## Acceptance criteria
1. Deleting an account removes all its rows; event counts drop; the user is signed out.
2. Data export contains exactly the user's own data.
3. Privacy test covers every public GET route (assert the dataset matches `Route::getRoutes()` filtered to public routes, so a new route without coverage fails).
4. Footer notice present on every page (browser test over the main pages).
