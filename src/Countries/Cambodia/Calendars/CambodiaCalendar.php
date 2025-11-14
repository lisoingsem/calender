<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Countries\Cambodia\Calendars;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Calendars\AbstractLunisolarCalendar;
use Lisoing\Calendar\Support\Cambodia\LunisolarCalculator;
use Lisoing\Calendar\Support\Cambodia\LunisolarConstants;
use Lisoing\Calendar\Support\Cambodia\LunarDate;
use RuntimeException;

/**
 * Cambodia Lunisolar Calendar implementation.
 *
 * This is an example of how to extend AbstractLunisolarCalendar
 * for a specific country's lunisolar calendar system.
 */
final class CambodiaCalendar extends AbstractLunisolarCalendar
{
    private ?LunisolarCalculator $calculator = null;

    public function __construct(?LunisolarCalculator $calculator = null)
    {
        parent::__construct(LunisolarConstants::TIMEZONE);
        $this->calculator = $calculator;
    }

    public function identifier(): string
    {
        return 'km';
    }

    protected function getCalculator(): object
    {
        if ($this->calculator === null) {
            $this->calculator = new LunisolarCalculator();
        }

        return $this->calculator;
    }

    protected function getDefaultTimezone(): string
    {
        return LunisolarConstants::TIMEZONE;
    }

    protected function getMonthSlug(int $monthIndex): string
    {
        return LunisolarConstants::lunarMonthSlug($monthIndex);
    }

    protected function getMonthIndex(string $monthSlug): int
    {
        $months = LunisolarConstants::lunarMonths();

        if (! array_key_exists($monthSlug, $months)) {
            throw new RuntimeException(sprintf('Unknown lunar month slug [%s].', $monthSlug));
        }

        return $months[$monthSlug];
    }

    protected function buildContext(CarbonImmutable $dateTime, object $lunarData): array
    {
        if (! $lunarData instanceof LunarDate) {
            return [];
        }

        // Get slugs for labels
        $weekdaySlug = LunisolarConstants::dayOfWeekSlug($lunarData->weekdayIndex());
        $animalYearSlug = LunisolarConstants::animalYearSlug($lunarData->animalYearIndex());
        $eraYearSlug = LunisolarConstants::eraYearSlug($lunarData->eraYearIndex());

        return [
            'weekday_slug' => $weekdaySlug,
            'animal_year_slug' => $animalYearSlug,
            'era_year_slug' => $eraYearSlug,
            'buddhist_era_year' => $lunarData->buddhistEraYear(),
            'animal_year_index' => $lunarData->animalYearIndex(),
            'era_year_index' => $lunarData->eraYearIndex(),
            'weekday_index' => $lunarData->weekdayIndex(),
        ];
    }

    protected function extractMonthSlug(object $lunarData): string
    {
        if (! $lunarData instanceof LunarDate) {
            throw new RuntimeException('Invalid lunar data type.');
        }

        return $lunarData->lunarMonthSlug();
    }

    protected function extractDay(object $lunarData): int
    {
        if (! $lunarData instanceof LunarDate) {
            throw new RuntimeException('Invalid lunar data type.');
        }

        return $lunarData->lunarDay()->day();
    }

    protected function extractPhase(object $lunarData): string
    {
        if (! $lunarData instanceof LunarDate) {
            throw new RuntimeException('Invalid lunar data type.');
        }

        return $lunarData->lunarDay()->phaseKey();
    }
}

