<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Answers for one event (M08): `{profiles: {id: bool}}`, true for going and
 * false for not going, one entry per trainer. Only the shape is checked here;
 * `SetAttendance` decides whose trainers and which events may be answered.
 */
class AttendanceRequest extends FormRequest
{
    /**
     * Any signed-in, verified account may send answers; `SetAttendance`
     * refuses a trainer that is not the account's own.
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
            'profiles' => ['required', 'array', 'min:1'],
            'profiles.*' => ['boolean'],
        ];
    }

    /**
     * Each key is a trainer id written as a plain whole number: "01" or "5abc"
     * would otherwise be read as trainer 1 or 5 by the database (review fix).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $answers = $this->input('profiles');
                $keys = is_array($answers) ? array_keys($answers) : [];

                foreach ($keys as $key) {
                    if (! preg_match('/^[1-9][0-9]*$/', (string) $key)) {
                        $validator->errors()->add('profiles', 'Each answer must be for one of your trainers.');

                        return;
                    }
                }
            },
        ];
    }
}
