<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The home page filters from the query string (`type[]`, `shop[]`).
 * Nothing is rejected: values it does not know are dropped, so an old or
 * edited shared link still shows a page (decisions.md, M05).
 */
class HomeFilterRequest extends FormRequest
{
    /**
     * No rules: bad values are dropped by the readers below, never rejected.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * The chosen tournament types; unknown types are dropped.
     *
     * @return list<EventType>
     */
    public function types(): array
    {
        return array_values($this->enums('type', EventType::class));
    }

    /**
     * The chosen shop ids. Only whole numbers are kept: MySQL would compare
     * a word like "abc" as 0 (Laravel docs, Queries, "Where Clauses").
     *
     * @return list<int>
     */
    public function shopIds(): array
    {
        $digitsOnly = array_filter(
            $this->array('shop'),
            fn (mixed $id): bool => is_string($id) && ctype_digit($id),
        );

        return array_values(array_map(intval(...), $digitsOnly));
    }
}
