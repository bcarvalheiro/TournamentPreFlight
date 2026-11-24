<?php

namespace App\Http\Requests;

use App\Models\PlayerProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new player profile (M07): name, Pokemon Player ID (POP ID) and birth year, all required.
 * The Pokemon Player ID (POP ID) is digits only, 1–10 long until its real format is confirmed
 * (decisions.md, open questions), and unique across every account. A child
 * profile also needs the parent's consent box (CLAUDE.md rule 2). An account
 * holds one profile aged 13 or older and at most 3 children (decisions.md, 24 Nov).
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
     * The account limits, checked once the birth year itself is valid: it
     * decides whether the new profile is the adult or a child.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('birth_year')) {
                return;
            }

            $user = $this->user();
            $isChild = PlayerProfile::needsParentalConsent($this->integer('birth_year'));

            if (! $isChild && ! $user->canAddAdultProfile()) {
                $validator->errors()->add('birth_year', 'Your account already has a trainer aged 13 or older. Anyone else 13 or older signs up with their own account.');
            }

            if ($isChild && ! $user->canAddChildProfile()) {
                $validator->errors()->add('birth_year', 'An account can hold up to '.PlayerProfile::MAX_CHILDREN.' children. To add more, email us at '.config('app.support_email').'.');
            }
        }];
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
            'consent.accepted' => 'A parent must give consent for a trainer under 13.',
        ];
    }

    /**
     * Field names as the form shows them, for Laravel's default messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pop_id' => 'Pokemon Player ID (POP ID)',
            'birth_year' => 'birth year',
        ];
    }
}
