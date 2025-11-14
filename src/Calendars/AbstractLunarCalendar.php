<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\LunarCalendarInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Abstract base class for purely lunar calendar implementations.
 *
 * This class provides common functionality for lunar calendars,
 * which follow only moon phases and do not align with solar years.
 *
 * Examples: Islamic/Hijri calendar (~354 days/year, no leap months).
 *
 * To create a new lunar calendar:
 * 1. Extend this class
 * 2. Implement the abstract methods
 * 3. Provide conversion logic using your preferred method (IntlDateFormatter, etc.)
 */
abstract class AbstractLunarCalendar implements LunarCalendarInterface
{
    protected string $timezone;

    /**
     * Create a new lunar calendar instance.
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
     * @return string Calendar identifier (e.g., 'islamic', 'hijri')
     */
    abstract public function identifier(): string;

    /**
     * Convert lunar date to solar (gregorian) date.
     * Override this method to provide conversion logic.
     *
     * @param  CalendarDate  $date  The lunar date
     * @return CarbonImmutable The gregorian date
     */
    abstract protected function convertToSolar(CalendarDate $date): CarbonImmutable;

    /**
     * Convert solar (gregorian) date to lunar date.
     * Override this method to provide conversion logic.
     *
     * @param  CarbonImmutable  $dateTime  The gregorian date
     * @return CalendarDate The lunar date
     */
    abstract protected function convertFromSolar(CarbonImmutable $dateTime): CalendarDate;

    /**
     * Build context data for CalendarDate.
     * Override this method to add custom context data.
     *
     * @param  CarbonImmutable  $dateTime  The gregorian date
     * @param  CalendarDate  $lunarDate  The lunar date
     * @return array<string, mixed> Context data array
     */
    protected function buildContext(CarbonImmutable $dateTime, CalendarDate $lunarDate): array
    {
        return [
            'gregorian_date' => $dateTime->toDateString(),
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
        // Try to use stored gregorian date if available
        $storedDate = $date->getContextValue('gregorian_date');

        $timezone = $date->getContextValue('timezone', $this->timezone);
        $tz = is_string($timezone) && $timezone !== '' ? $timezone : $this->timezone;

        if (is_string($storedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $storedDate) === 1) {
            $immutable = CarbonImmutable::createFromFormat('Y-m-d', $storedDate, $this->timezone);

            if ($immutable instanceof CarbonImmutable) {
                return $immutable->setTimezone($tz);
            }
        }

        // Convert using abstract method
        $solar = $this->convertToSolar($date);

        return $solar->setTimezone($tz);
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        $immutable = CarbonImmutable::instance($dateTime)
            ->setTimezone($this->timezone)
            ->startOfDay();

        $lunarDate = $this->convertFromSolar($immutable);

        // Merge context with additional data
        $context = array_merge(
            $lunarDate->getContext(),
            $this->buildContext($immutable, $lunarDate)
        );

        return new CalendarDate(
            year: $lunarDate->getYear(),
            month: $lunarDate->getMonth(),
            day: $lunarDate->getDay(),
            calendar: $this->identifier(),
            context: $context
        );
    }
}

