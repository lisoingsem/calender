<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

final class NepalCalendar extends GregorianCalendar
{
    public function __construct()
    {
        parent::__construct('Asia/Kathmandu');
    }

    public function identifier(): string
    {
        return 'nepal_gregorian';
    }
}

