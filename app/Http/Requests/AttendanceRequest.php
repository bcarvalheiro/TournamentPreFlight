<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
}
