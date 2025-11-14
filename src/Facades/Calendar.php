<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Facades;

use Illuminate\Support\Facades\Facade;
use Lisoing\Calendar\CalendarManager;

/**
 * @method static string|null getDefaultCalendar()
 * @method static \Lisoing\Calendar\Support\CalendarContext for(string|\Lisoing\Calendar\Contracts\CalendarInterface $calendar)
 * @method static \Lisoing\Calendar\Support\CalendarContext using(string|\Lisoing\Calendar\Contracts\CalendarInterface $calendar)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate convert(\Lisoing\Calendar\ValueObjects\CalendarDate $date, string $targetIdentifier)
 * @method static \Carbon\CarbonInterface toDateTime(\Lisoing\Calendar\ValueObjects\CalendarDate $date)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate fromDateTime(\Carbon\CarbonInterface $dateTime, ?string $calendarIdentifier = null)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate toLunar(\Carbon\CarbonInterface $dateTime, ?string $calendarIdentifier = null)
 * @method static \Carbon\CarbonInterface toSolar(\Lisoing\Calendar\ValueObjects\CalendarDate $date, string $targetIdentifier = 'gregorian')
 */
final class Calendar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CalendarManager::class;
    }
}
