<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars\Traits;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Trait for lunar calendar helper methods.
 *
 * This trait provides helper methods for working with purely lunar calendars
 * (moon-based only, no solar alignment).
 *
 * Can be used by countries or calendar implementations that need lunar calendar support.
 */
trait LunarCalendar
{
    /**
     * Convert a lunar date to gregorian date.
     * Override this method in your class to provide conversion logic.
     *
     * @param  CalendarDate  $lunarDate  The lunar date
     * @return CarbonImmutable The gregorian date
     */
    protected function lunarToGregorian(CalendarDate $lunarDate): CarbonImmutable
    {
        // This should be overridden by classes using this trait
        throw new \RuntimeException('lunarToGregorian() must be implemented by the class using LunarCalendar trait.');
    }

    /**
     * Convert a gregorian date to lunar date.
     * Override this method in your class to provide conversion logic.
     *
     * @param  CarbonImmutable  $gregorianDate  The gregorian date
     * @return CalendarDate The lunar date
     */
    protected function gregorianToLunar(CarbonImmutable $gregorianDate): CalendarDate
    {
        // This should be overridden by classes using this trait
        throw new \RuntimeException('gregorianToLunar() must be implemented by the class using LunarCalendar trait.');
    }

    /**
     * Get the lunar calendar identifier.
     * Override this method to return your calendar's identifier.
     *
     * @return string Calendar identifier (e.g., 'islamic', 'hijri')
     */
    protected function lunarCalendarIdentifier(): string
    {
        return 'lunar';
    }
}

