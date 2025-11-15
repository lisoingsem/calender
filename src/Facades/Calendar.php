<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Facades;

use Illuminate\Support\Facades\Facade;
use Lisoing\Calendar\CalendarManager;

/**
 * @method static string|null getDefaultCalendar()
 * @method static string getFallbackLocale()
 * @method static string resolveLocale(?string $locale)
 * @method static \Lisoing\Calendar\Support\CalendarContext for(string|\Lisoing\Calendar\Contracts\CalendarInterface|class-string<\Lisoing\Countries\Country> $calendar)
 * @method static \Lisoing\Calendar\Support\CalendarContext using(string|\Lisoing\Calendar\Contracts\CalendarInterface|class-string<\Lisoing\Countries\Country> $calendar)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate convert(\Lisoing\Calendar\ValueObjects\CalendarDate $date, string $targetIdentifier)
 * @method static \Carbon\CarbonInterface toDateTime(\Lisoing\Calendar\ValueObjects\CalendarDate $date)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate fromDateTime(\Carbon\CarbonInterface $dateTime, ?string $calendarIdentifier = null)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate toLunar(\Carbon\CarbonInterface $dateTime, ?string $calendarIdentifier = null)
 * @method static bool isLunar(string $identifier)
 * @method static bool isLunisolar(string $identifier)
 * @method static bool isSolar(string $identifier)
 * @method static \Carbon\CarbonInterface toSolar(\Lisoing\Calendar\ValueObjects\CalendarDate $date, string $targetIdentifier = 'gregorian')
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate parse(string $date, ?string $calendar = null, ?string $timezone = null)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate now(?string $calendar = null, ?string $timezone = null)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate create(int $year, int $month, int $day, ?string $calendar = null, ?string $timezone = null)
 * 
 * CalendarContext methods:
 * @method \Lisoing\Calendar\Support\HolidayContext holidays(?int $year = null, ?string $locale = null)
 */
final class Calendar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CalendarManager::class;
    }
}
