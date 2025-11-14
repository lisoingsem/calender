<?php

declare(strict_types=1);

namespace Lisoing;

use Lisoing\Calendar\Facades\Calendar as CalendarFacade;

/**
 * Proxy to the calendar facade for ergonomic `use Lisoing\Calendar;` imports.
 */
final class Calendar
{
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return CalendarFacade::__callStatic($method, $arguments);
    }
}

