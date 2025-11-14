<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Carbon\CarbonInterface;
use Lisoing\Calendar\CalendarManager;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class CalendarContext
{
    public function __construct(
        private readonly CalendarManager $manager,
        private readonly string $targetIdentifier
    ) {}

    public function identifier(): string
    {
        return $this->targetIdentifier;
    }

    public function fromCarbon(CarbonInterface $dateTime): CalendarDate
    {
        return $this->manager->fromDateTime($dateTime, $this->targetIdentifier);
    }

    public function fromCalendar(CalendarDate $date): CalendarDate
    {
        return $this->manager->convert($date, $this->targetIdentifier);
    }

    public function convert(CalendarDate $date): CalendarDate
    {
        return $this->fromCalendar($date);
    }

    public function toCarbon(CalendarDate $date): CarbonInterface
    {
        return $this->manager->toDateTime($date);
    }
}

