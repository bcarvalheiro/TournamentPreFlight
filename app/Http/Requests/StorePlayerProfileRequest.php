<?php

namespace App\Http\Requests;

use App\Models\PlayerProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;

/**
 * A new player profile (M07): name, Pokemon Player ID (POP ID) and birth year, all required.
 * The Pokemon Player ID (POP ID) is digits only, 1–10 long until its real format is confirmed
 * (decisions.md, open questions), and unique across every account. A child
 * profile also needs the parent's consent box (CLAUDE.md rule 2).
 */
class StorePlayerProfileRequest extends FormRequest
{
    /**
     * Any signed-in, verified account may add a profile to itself; the routes'
     * `auth` and `verified` middleware already checked that.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|ConditionalRules|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'pop_id' => ['required', 'string', 'digits_between:1,10', 'unique:player_profiles,pop_id'],
            'birth_year' => ['required', 'integer', 'between:1920,'.now('Europe/Lisbon')->year],
            // Asked only for a child; for anyone else the box is dropped (`exclude`), so it is never stored.
            // A birth year that is not a number reads as 0, never a child: only its own error shows.
            'consent' => Rule::when(PlayerProfile::needsParentalConsent($this->integer('birth_year')), ['accepted'], ['exclude']),
        ];
    }

    /**
     * The duplicate message never says whose profile holds the Pokemon Player ID (POP ID).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pop_id.unique' => 'This Pokemon Player ID (POP ID) is already on another profile.',
            'consent.accepted' => 'A parent must give consent for a player under 13.',
        ];
    }
}
