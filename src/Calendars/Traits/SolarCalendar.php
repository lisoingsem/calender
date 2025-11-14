<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars\Traits;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Trait for solar calendar helper methods.
 *
 * This trait provides helper methods for working with solar calendars
 * (sun-based, fixed month lengths, 365/366 days per year).
 *
 * Can be used by countries or calendar implementations that need solar calendar support.
 */
trait SolarCalendar
{
    /**
     * Convert a solar date to gregorian date.
     * For most solar calendars, this is a direct mapping.
     * Override this method if your calendar has different calculations.
     *
     * @param  CalendarDate  $solarDate  The solar date
     * @return CarbonImmutable The gregorian date
     */
    protected function solarToGregorian(CalendarDate $solarDate): CarbonImmutable
    {
        // Default implementation: direct mapping for most solar calendars
        return CarbonImmutable::create(
            $solarDate->getYear(),
            $solarDate->getMonth(),
            $solarDate->getDay(),
            0,
            0,
            0
        );
    }

    /**
     * Convert a gregorian date to solar date.
     * For most solar calendars, this is a direct mapping.
     * Override this method if your calendar has different calculations.
     *
     * @param  CarbonImmutable  $gregorianDate  The gregorian date
     * @return CalendarDate The solar date
     */
    protected function gregorianToSolar(CarbonImmutable $gregorianDate): CalendarDate
    {
        // Default implementation: direct mapping for most solar calendars
        return new CalendarDate(
            year: (int) $gregorianDate->year,
            month: (int) $gregorianDate->month,
            day: (int) $gregorianDate->day,
            calendar: $this->solarCalendarIdentifier(),
            context: [
                'timezone' => $gregorianDate->timezoneName,
            ]
        );
    }

    /**
     * Get the solar calendar identifier.
     * Override this method to return your calendar's identifier.
     *
     * @return string Calendar identifier (e.g., 'gregorian', 'julian')
     */
    protected function solarCalendarIdentifier(): string
    {
        return 'solar';
    }

    /**
     * Check if a year is a leap year.
     * Override this method to provide custom leap year logic.
     *
     * @param  int  $year  The year to check
     * @return bool True if the year is a leap year
     */
    protected function isLeapYear(int $year): bool
    {
        // Default: Gregorian leap year rule
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}

