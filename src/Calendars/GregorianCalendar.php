<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\SolarCalendarInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

class GregorianCalendar implements SolarCalendarInterface
{
    private string $timezone;

    public function __construct(string $defaultTimezone = 'UTC')
    {
        $this->timezone = $defaultTimezone;
    }

    public function identifier(): string
    {
        return 'gregorian';
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

        return new CalendarDate(
            year: (int) $immutable->year,
            month: (int) $immutable->month,
            day: (int) $immutable->day,
            calendar: $this->identifier(),
            context: [
                'timezone' => $immutable->timezoneName,
            ]
        );
    }
}

