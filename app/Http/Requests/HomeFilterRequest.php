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
     * The chosen date preset (`when`); anything else means any time, and so
     * does "custom" when no usable date was picked.
     */
    public function datePreset(): string
    {
        $when = $this->query('when');

        if ($when === 'custom') {
            return $this->from() || $this->to() ? 'custom' : 'any';
        }

        return in_array($when, self::DATE_PRESETS, true) ? $when : 'any';
    }

    /**
     * The chosen dates in Lisbon calendar days: the start of the first day and
     * the end of the last (architecture.md, "Time"). Null means any time; a
     * null end means no end. An open From starts today: ended events are
     * never listed anyway.
     *
     * @return array{CarbonImmutable, CarbonImmutable|null}|null
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
            'custom' => [$this->from() ?? $today, $this->to()?->endOfDay()],
            default => null,
        };
    }

    /**
     * The picked From day (Lisbon midnight). Null when missing or unusable,
     * or when To is before it (the range contradicts itself).
     */
    public function from(): ?CarbonImmutable
    {
        return $this->pickedDaysAreInOrder() ? $this->pickedDay('from') : null;
    }

    /**
     * The picked To day (Lisbon midnight). Null when missing or unusable,
     * or when it is before From.
     */
    public function to(): ?CarbonImmutable
    {
        return $this->pickedDaysAreInOrder() ? $this->pickedDay('to') : null;
    }

    /** True unless both days were picked and To comes before From. */
    private function pickedDaysAreInOrder(): bool
    {
        $from = $this->pickedDay('from');
        $to = $this->pickedDay('to');

        return ! $from || ! $to || $to->gte($from);
    }

    /**
     * A picked day as Lisbon midnight, or null when it is not a real
     * YYYY-MM-DD day (the round trip catches days like 31 February) or is
     * before today.
     */
    private function pickedDay(string $key): ?CarbonImmutable
    {
        $value = $this->query($key);

        if (! is_string($value) || ! CarbonImmutable::canBeCreatedFromFormat($value, 'Y-m-d')) {
            return null;
        }

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'Europe/Lisbon');
        $today = CarbonImmutable::now('Europe/Lisbon')->startOfDay();

        return $day && $day->format('Y-m-d') === $value && $day->gte($today) ? $day : null;
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
