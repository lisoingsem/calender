<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

abstract class AbstractCalendar implements CalendarInterface
{
    abstract public function identifier(): string;

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        return CarbonImmutable::create(
            $date->getYear(),
            $date->getMonth(),
            $date->getDay()
        );
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        return CalendarDate::fromDateTime($dateTime, $this->identifier());
    }
}
