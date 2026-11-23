<?php

namespace App\Http\Controllers\Account;

use App\Actions\CreatePlayerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerProfileRequest;
use Illuminate\Http\RedirectResponse;

/**
 * The signed-in account's own player profiles (M07).
 */
class PlayerProfileController extends Controller
{
    /**
     * Save a new profile, then go back to the page the player gate blocked
     * (its intended URL), where a pending "I'm going" waits; home otherwise.
     */
    public function store(StorePlayerProfileRequest $request, CreatePlayerProfile $createPlayerProfile): RedirectResponse
    {
        /** @var array{name: string, pop_id: string, birth_year: int|string} $data */
        $data = $request->validated();

        $createPlayerProfile->handle($request->user(), $data);

        return redirect()->intended(route('home'));
    }
}
