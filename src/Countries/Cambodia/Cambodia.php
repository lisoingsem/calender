<?php

declare(strict_types=1);

namespace Lisoing\Countries;

use Lisoing\Calendar\Countries\Cambodia\Calendars\CambodiaCalendar;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Countries\Cambodia\Holidays as HolidaysProvider;

final class Cambodia extends Country
{

    public static function defaultLocale(): ?string
    {
        return 'km';
    }

    public static function timezone(): ?string
    {
        return 'Asia/Phnom_Penh';
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
        return HolidaysProvider::class;
    }

    protected static function countryCode(): string
    {
        return 'KH';
    }
}

