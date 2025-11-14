<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Lisoing\Calendar\Calendars\AbstractSolarCalendar;

final class GregorianCalendar extends AbstractSolarCalendar
{
    public function identifier(): string
    {
        return 'gregorian';
    }
}

