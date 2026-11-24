<?php

namespace App\Http\Controllers\Account;

use App\Actions\CreatePlayerProfile;
use App\Actions\UpdatePlayerProfile;
use App\Data\Private\OwnProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerProfileRequest;
use App\Models\PlayerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in account's own player profiles (M07).
 */
class PlayerProfileController extends Controller
{
    /**
     * The account's own players, oldest first. Only this account's profiles
     * can be listed: the query starts from the signed-in user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('profiles/index', [
            'profiles' => $user->playerProfiles()->oldest('id')->get()
                ->map(OwnProfileData::fromModel(...)),
            'isFull' => ! $user->canAddAdultProfile() && ! $user->canAddChildProfile(),
            'supportEmail' => config('app.support_email'),
        ]);
    }

    /**
     * The "Add a trainer" form: the account's own profile or a child's. After
     * "I'm going" the player gate sends a user without a profile here, and the
     * page offers "Not now".
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('profiles/create', [
            'signingInToGo' => $request->session()->has('pending_intent'),
            'consentFromYear' => PlayerProfile::consentFromBirthYear(),
            'canAddAdult' => $user->canAddAdultProfile(),
            'canAddChild' => $user->canAddChildProfile(),
            'supportEmail' => config('app.support_email'),
        ]);
    }

    /**
     * Save a new profile, then go back to the page the player gate blocked
     * (its intended URL), where a pending "I'm going" waits. Otherwise the
     * first profile goes home and any later one back to the list.
     */
    public function store(PlayerProfileRequest $request, CreatePlayerProfile $createPlayerProfile): RedirectResponse
    {
        /** @var array{name: string, pop_id: string, birth_year: int|string, consent?: mixed} $data */
        $data = $request->validated();

        $user = $request->user();
        $isFirst = ! $user->playerProfiles()->exists();

        $createPlayerProfile->handle($user, $data);

        return redirect()->intended(route($isFirst ? 'home' : 'profiles.index'));
    }

    /**
     * The edit form, prefilled with the trainer (only its owner reaches it:
     * the route's `can:update` middleware).
     */
    public function edit(PlayerProfile $profile): Response
    {
        return Inertia::render('profiles/edit', [
            'profile' => OwnProfileData::fromModel($profile),
            'consentFromYear' => PlayerProfile::consentFromBirthYear(),
        ]);
    }

    /**
     * Save the edited trainer and go back to the list.
     */
    public function update(PlayerProfileRequest $request, PlayerProfile $profile, UpdatePlayerProfile $updatePlayerProfile): RedirectResponse
    {
        /** @var array{name: string, pop_id: string, birth_year: int|string, consent?: mixed} $data */
        $data = $request->validated();

        $updatePlayerProfile->handle($request->user(), $profile, $data);

        return redirect()->route('profiles.index');
    }

    /**
     * Remove the trainer (only its owner gets here: `can:delete`). Their
     * "I'm going" answers go too: the attendances foreign key cascades.
     */
    public function destroy(PlayerProfile $profile): RedirectResponse
    {
        $profile->delete();

        return redirect()->route('profiles.index');
    }
}
