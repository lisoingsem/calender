<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Facades;

use Illuminate\Support\Facades\Facade;
use Lisoing\Calendar\Support\CalendarToolkit;

/**
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate toSolar(\Lisoing\Calendar\ValueObjects\CalendarDate|array $lunar, string $targetCalendar = 'gregorian')
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate toLunar(\Carbon\CarbonInterface $dateTime, string $calendarIdentifier = 'khmer')
 * @method static string format(\Lisoing\Calendar\ValueObjects\CalendarDate|array $date, ?string $locale = null)
 * @method static \Lisoing\Calendar\ValueObjects\HolidayCollection holidays(int $year, string $countryCode, ?string $locale = null)
 * @method static bool isHolidaysEnabled()
 * @method static \Illuminate\Support\Collection calendars()
 */
final class Toolkit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CalendarToolkit::class;
    }
}

