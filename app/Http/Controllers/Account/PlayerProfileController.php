<?php

namespace App\Http\Controllers\Account;

use App\Actions\CreatePlayerProfile;
use App\Data\Private\OwnProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerProfileRequest;
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
        return Inertia::render('profiles/index', [
            'profiles' => $request->user()->playerProfiles()->oldest('id')->get()
                ->map(OwnProfileData::fromModel(...)),
        ]);
    }

    /**
     * The "Add a player" form: the account's own profile or a child's. After
     * "I'm going" the player gate sends a user without a profile here, and the
     * page offers "Not now".
     */
    public function create(Request $request): Response
    {
        return Inertia::render('profiles/create', [
            'signingInToGo' => $request->session()->has('pending_intent'),
            'consentFromYear' => PlayerProfile::consentFromBirthYear(),
        ]);
    }

    /**
     * Save a new profile, then go back to the page the player gate blocked
     * (its intended URL), where a pending "I'm going" waits. Otherwise the
     * first profile goes home and any later one back to the list.
     */
    public function store(StorePlayerProfileRequest $request, CreatePlayerProfile $createPlayerProfile): RedirectResponse
    {
        /** @var array{name: string, pop_id: string, birth_year: int|string, consent?: mixed} $data */
        $data = $request->validated();

        $user = $request->user();
        $isFirst = ! $user->playerProfiles()->exists();

        $createPlayerProfile->handle($user, $data);

        return redirect()->intended(route($isFirst ? 'home' : 'profiles.index'));
    }
}
