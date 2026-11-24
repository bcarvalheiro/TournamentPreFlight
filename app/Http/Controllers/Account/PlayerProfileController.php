<?php

namespace App\Http\Controllers\Account;

use App\Actions\CreatePlayerProfile;
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
     * (its intended URL), where a pending "I'm going" waits; home otherwise.
     */
    public function store(StorePlayerProfileRequest $request, CreatePlayerProfile $createPlayerProfile): RedirectResponse
    {
        /** @var array{name: string, pop_id: string, birth_year: int|string, consent?: mixed} $data */
        $data = $request->validated();

        $createPlayerProfile->handle($request->user(), $data);

        return redirect()->intended(route('home'));
    }
}
