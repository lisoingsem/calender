<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Contracts;

/**
 * Interface for purely lunar calendars (moon-based only).
 *
 * Lunar calendars follow only moon phases and do not align with solar years.
 * Examples: Islamic/Hijri calendar (~354 days/year, no leap months).
 */
interface LunarCalendarInterface extends CalendarInterface, ConfigurableCalendarInterface
{
}

