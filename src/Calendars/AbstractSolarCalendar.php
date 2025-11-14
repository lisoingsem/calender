<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\SolarCalendarInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Abstract base class for solar calendar implementations.
 *
 * This class provides common functionality for solar calendars,
 * which follow the solar year (365/366 days) with fixed month lengths.
 *
 * Examples: Gregorian calendar, Julian calendar.
 *
 * To create a new solar calendar:
 * 1. Extend this class
 * 2. Implement the identifier() method
 * 3. Override conversion methods if your calendar has different month/day calculations
 */
abstract class AbstractSolarCalendar implements SolarCalendarInterface
{
    protected string $timezone;

    /**
     * Create a new solar calendar instance.
     *
     * @param  string  $defaultTimezone  Default timezone for this calendar
     */
    public function __construct(string $defaultTimezone = 'UTC')
    {
        $this->timezone = $defaultTimezone;
    }

    /**
     * Get the calendar identifier.
     * Override this method to return your calendar's identifier.
     *
     * @return string Calendar identifier (e.g., 'gregorian', 'julian')
     */
    abstract public function identifier(): string;

    /**
     * Build context data for CalendarDate.
     * Override this method to add custom context data.
     *
     * @param  CarbonImmutable  $dateTime  The date
     * @return array<string, mixed> Context data array
     */
    protected function buildContext(CarbonImmutable $dateTime): array
    {
        return [
            'timezone' => $dateTime->timezoneName,
        ];
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
        $timezone = $date->getContextValue('timezone', $this->timezone);
        $tz = is_string($timezone) && $timezone !== '' ? $timezone : $this->timezone;

        return CarbonImmutable::create(
            $date->getYear(),
            $date->getMonth(),
            $date->getDay(),
            0,
            0,
            0,
            $tz
        );
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        $immutable = CarbonImmutable::instance($dateTime)->setTimezone($this->timezone);

        $context = $this->buildContext($immutable);

        return new CalendarDate(
            year: (int) $immutable->year,
            month: (int) $immutable->month,
            day: (int) $immutable->day,
            calendar: $this->identifier(),
            context: $context
        );
    }
}

