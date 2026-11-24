<?php

namespace App\Http\Requests;

use App\Models\PlayerProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A trainer (player profile) being added or edited (M07): name, Pokemon
 * Player ID (POP ID) and birth year, all required. The POP ID is digits only,
 * 1–10 long until its real format is confirmed (decisions.md, open questions),
 * and unique across every account. A trainer who becomes a child needs the
 * parent's consent box (CLAUDE.md rule 2); an existing child keeps theirs. An
 * account holds one trainer aged 13 or older and at most 3 children
 * (decisions.md, 24 Nov). On store the route has no {profile}.
 */
class PlayerProfileRequest extends FormRequest
{
    /**
     * Any signed-in, verified account may add a trainer to itself, and the
     * `can:update` route middleware already decided who may edit one.
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
        $becomesChild = $this->isChild() && ! ($this->profile()->is_child ?? false);

        return [
            'name' => ['required', 'string', 'max:255'],
            'pop_id' => ['required', 'string', 'digits_between:1,10', Rule::unique('player_profiles', 'pop_id')->ignore($this->profile())],
            'birth_year' => ['required', 'integer', 'between:1920,'.now('Europe/Lisbon')->year],
            // Asked only of a trainer who becomes a child; otherwise the box is dropped (`exclude`), never stored.
            // A birth year that is not a number reads as 0, never a child: only its own error shows.
            'consent' => Rule::when($becomesChild, ['accepted'], ['exclude']),
        ];
    }

    /**
     * The account limits, checked once the birth year itself is valid: it
     * decides whether the trainer is the adult or a child. An edit that keeps
     * the trainer on the same side changes no count.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $profile = $this->profile();
            $isChild = $this->isChild();

            if ($validator->errors()->has('birth_year') || ($profile !== null && $profile->is_child === $isChild)) {
                return;
            }

            $user = $this->user();

            if (! $isChild && ! $user->canAddAdultProfile()) {
                $validator->errors()->add('birth_year', 'Your account already has a trainer aged 13 or older. Anyone else 13 or older signs up with their own account.');
            }

            if ($isChild && ! $user->canAddChildProfile()) {
                $validator->errors()->add('birth_year', 'An account can hold up to '.PlayerProfile::MAX_CHILDREN.' children. To add more, email us at '.config('app.support_email').'.');
            }
        }];
    }

    /**
     * The trainer being edited; null when one is being added.
     */
    private function profile(): ?PlayerProfile
    {
        $profile = $this->route('profile');

        return $profile instanceof PlayerProfile ? $profile : null;
    }

    /**
     * Whether the submitted birth year makes the trainer a child.
     */
    private function isChild(): bool
    {
        return PlayerProfile::needsParentalConsent($this->integer('birth_year'));
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
