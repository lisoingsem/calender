<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\LunisolarCalendarInterface;
use Lisoing\Calendar\Support\Khmer\LunisolarCalculator;
use Lisoing\Calendar\Support\Khmer\LunisolarConstants;
use Lisoing\Calendar\ValueObjects\CalendarDate;
use RuntimeException;

final class CambodiaCalendar implements LunisolarCalendarInterface
{
    private string $timezone;

    public function __construct(
        private readonly LunisolarCalculator $calculator = new LunisolarCalculator()
    ) {
        $this->timezone = LunisolarConstants::TIMEZONE;
    }

    public function identifier(): string
    {
        return 'km';
    }

    public function configure(array $settings): void
    {
        $timezone = $settings['timezone'] ?? null;

        if (is_string($timezone) && $timezone !== '') {
            $this->timezone = $timezone;
        }
    }

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        $storedDate = $date->getContextValue('gregorian_date');

        $timezone = $date->getContextValue('timezone', $this->timezone);
        $tz = is_string($timezone) && $timezone !== '' ? $timezone : $this->timezone;

        if (is_string($storedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $storedDate) === 1) {
            $immutable = CarbonImmutable::createFromFormat('Y-m-d', $storedDate, LunisolarConstants::TIMEZONE);

            if ($immutable instanceof CarbonImmutable) {
                return $immutable->setTimezone($tz);
            }
        }

        $monthSlug = $this->resolveMonthSlug($date);
        $phase = $this->resolvePhase($date);

        $solar = $this->calculator->toSolar(
            gregorianYear: $date->getYear(),
            monthSlug: $monthSlug,
            phaseDay: $date->getDay(),
            phase: $phase
        );

        return $solar->setTimezone($tz);
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        $immutable = CarbonImmutable::instance($dateTime)
            ->setTimezone($this->timezone)
            ->startOfDay();

        $lunar = $this->calculator->toLunar($immutable);
        $monthSlug = $lunar->lunarMonthSlug();
        $monthIndex = $this->monthIndexFromSlug($monthSlug);

        $lunarDay = $lunar->lunarDay();
        $phase = $lunarDay->phaseKey();

        // Get slugs for labels
        $weekdaySlug = LunisolarConstants::dayOfWeekSlug($lunar->weekdayIndex());
        $animalYearSlug = LunisolarConstants::animalYearSlug($lunar->animalYearIndex());
        $eraYearSlug = LunisolarConstants::eraYearSlug($lunar->eraYearIndex());

        return new CalendarDate(
            year: (int) $immutable->year,
            month: $monthIndex + 1,
            day: $lunarDay->day(),
            calendar: $this->identifier(),
            context: [
                'phase' => $phase,
                'month_slug' => $monthSlug,
                'weekday_slug' => $weekdaySlug,
                'animal_year_slug' => $animalYearSlug,
                'era_year_slug' => $eraYearSlug,
                'gregorian_date' => $immutable->toDateString(),
                'timezone' => $immutable->timezoneName,
                'buddhist_era_year' => $lunar->buddhistEraYear(),
                'animal_year_index' => $lunar->animalYearIndex(),
                'era_year_index' => $lunar->eraYearIndex(),
                'weekday_index' => $lunar->weekdayIndex(),
            ]
        );
    }

    private function resolveMonthSlug(CalendarDate $date): string
    {
        $slug = $date->getContextValue('month_slug');

        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        return LunisolarConstants::lunarMonthSlug($date->getMonth() - 1);
    }

    private function resolvePhase(CalendarDate $date): string
    {
        $phase = $date->getContextValue('phase');

        if (is_string($phase) && in_array($phase, ['waxing', 'waning'], true)) {
            return $phase;
        }

        return $date->getDay() <= 15 ? 'waxing' : 'waning';
    }

    private function monthIndexFromSlug(string $slug): int
    {
        $months = LunisolarConstants::lunarMonths();

        if (! array_key_exists($slug, $months)) {
            throw new RuntimeException(sprintf('Unknown lunar month slug [%s].', $slug));
        }

        return $months[$slug];
    }
}

