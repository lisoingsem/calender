<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class GregorianCalendar extends AbstractCalendar
{
    public function identifier(): string
    {
        return 'gregorian';
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        $immutable = CarbonImmutable::instance($dateTime);

        return parent::fromDateTime($immutable)
            ->withContext('timezone', $immutable->timezoneName);
    }

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        $dateTime = parent::toDateTime($date);
        $timezone = $date->getContextValue('timezone');

        if (is_string($timezone) && $timezone !== '') {
            return CarbonImmutable::instance($dateTime)->setTimezone($timezone);
        }

        return $dateTime;
    }
}
