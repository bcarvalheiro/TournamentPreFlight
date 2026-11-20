<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The home page filters from the query string (`type[]`, `shop[]`, `when`).
 * Nothing is rejected: values it does not know are dropped, so an old or
 * edited shared link still shows a page (decisions.md, M05).
 */
class HomeFilterRequest extends FormRequest
{
    /** The date presets of the filter sheet, besides "any" (product.md, Home page). */
    private const array DATE_PRESETS = ['week', 'weekend', '7', '30'];

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
     * The chosen date preset (`when`); anything else means any time.
     */
    public function datePreset(): string
    {
        $when = $this->query('when');

        return in_array($when, self::DATE_PRESETS, true) ? $when : 'any';
    }

    /**
     * The chosen dates in Lisbon calendar days: the start of the first day and
     * the end of the last (architecture.md, "Time"). Null means any time.
     *
     * @return array{CarbonImmutable, CarbonImmutable}|null
     */
    public function dateRange(): ?array
    {
        $today = CarbonImmutable::now('Europe/Lisbon')->startOfDay();
        $sundayNight = $today->endOfWeek(CarbonInterface::SUNDAY);

        return match ($this->datePreset()) {
            'week' => [$today, $sundayNight],
            'weekend' => [$today->isWeekend() ? $today : $today->next(CarbonInterface::SATURDAY), $sundayNight],
            '7' => [$today, $today->addDays(6)->endOfDay()],
            '30' => [$today, $today->addDays(29)->endOfDay()],
            default => null,
        };
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
