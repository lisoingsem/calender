<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Contracts;

use Carbon\CarbonInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

interface CalendarInterface
{
    public function identifier(): string;

    public function toDateTime(CalendarDate $date): CarbonInterface;

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate;
}
