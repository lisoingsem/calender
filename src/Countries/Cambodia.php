<?php

declare(strict_types=1);

namespace Lisoing\Countries;

use Lisoing\Calendar\Calendars\CambodiaCalendar;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Holidays\Countries\Cambodia as CambodiaProvider;

final class Cambodia extends Country
{

    public static function defaultLocale(): ?string
    {
        return 'km';
    }

    public static function calendar(): string
    {
        return 'km';
    }

    /**
     * @return class-string<CalendarInterface>
     */
    public static function calendarClass(): string
    {
        return CambodiaCalendar::class;
    }

    protected static function providerClass(): string
    {
        return CambodiaProvider::class;
    }

    protected static function countryCode(): string
    {
        return 'KH';
    }
}

