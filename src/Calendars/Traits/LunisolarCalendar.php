<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars\Traits;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Trait for lunisolar calendar helper methods.
 *
 * This trait provides helper methods for working with lunisolar calendars
 * (moon phases + solar year alignment via leap months).
 *
 * Can be used by countries or calendar implementations that need lunisolar calendar support.
 */
trait LunisolarCalendar
{
    /**
     * Convert a lunisolar date to gregorian date.
     * Override this method in your class to provide conversion logic.
     *
     * @param  CalendarDate  $lunisolarDate  The lunisolar date
     * @return CarbonImmutable The gregorian date
     */
    protected function lunisolarToGregorian(CalendarDate $lunisolarDate): CarbonImmutable
    {
        // This should be overridden by classes using this trait
        throw new \RuntimeException('lunisolarToGregorian() must be implemented by the class using LunisolarCalendar trait.');
    }

    /**
     * Convert a gregorian date to lunisolar date.
     * Override this method in your class to provide conversion logic.
     *
     * @param  CarbonImmutable  $gregorianDate  The gregorian date
     * @return CalendarDate The lunisolar date
     */
    protected function gregorianToLunisolar(CarbonImmutable $gregorianDate): CalendarDate
    {
        // This should be overridden by classes using this trait
        throw new \RuntimeException('gregorianToLunisolar() must be implemented by the class using LunisolarCalendar trait.');
    }

    /**
     * Get the lunisolar calendar identifier.
     * Override this method to return your calendar's identifier.
     *
     * @return string Calendar identifier (e.g., 'km', 'chinese')
     */
    protected function lunisolarCalendarIdentifier(): string
    {
        return 'lunisolar';
    }

    /**
     * Check if a year has a leap month.
     * Override this method to provide leap month detection logic.
     *
     * @param  int  $year  The year to check
     * @return bool True if the year has a leap month
     */
    protected function hasLeapMonth(int $year): bool
    {
        // This should be overridden by classes using this trait
        return false;
    }
}

