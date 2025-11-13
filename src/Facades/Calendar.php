<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Facades;

use Illuminate\Support\Facades\Facade;
use Lisoing\Calendar\CalendarManager;

/**
 * @method static string getDefaultCalendar()
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate convert(\Lisoing\Calendar\ValueObjects\CalendarDate $date, string $targetIdentifier)
 * @method static \Carbon\CarbonInterface toDateTime(\Lisoing\Calendar\ValueObjects\CalendarDate $date)
 * @method static \Lisoing\Calendar\ValueObjects\CalendarDate fromDateTime(\Carbon\CarbonInterface $dateTime, ?string $calendarIdentifier = null)
 */
final class Calendar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CalendarManager::class;
    }
}

