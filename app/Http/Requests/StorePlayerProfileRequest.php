<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new player profile (M07): name, Pokemon Player ID (POP ID) and birth year, all required.
 * The Pokemon Player ID (POP ID) is digits only, 1–10 long until its real format is confirmed
 * (decisions.md, open questions), and unique across every account.
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'pop_id' => ['required', 'string', 'digits_between:1,10', 'unique:player_profiles,pop_id'],
            'birth_year' => ['required', 'integer', 'between:1920,'.now('Europe/Lisbon')->year],
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
        ];
    }
}
