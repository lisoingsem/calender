<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

final class GregorianCalendar extends AbstractCalendar
{
    public function identifier(): string
    {
        return 'gregorian';
    }
}
