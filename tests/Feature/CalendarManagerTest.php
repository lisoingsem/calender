<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\CalendarManager;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class CalendarManagerTest extends TestCase
{
    public function test_it_converts_between_registered_calendars(): void
    {
        /** @var CalendarManager $manager */
        $manager = $this->app->make(CalendarManager::class);

        $khmerNewYear = new CalendarDate(2025, 13, 1, 'khmer_chhankitek');

        $gregorian = $manager->convert($khmerNewYear, 'gregorian');

        $this->assertSame('gregorian', $gregorian->getCalendar());
        $this->assertSame(2025, $gregorian->getYear());
        $this->assertSame(4, $gregorian->getMonth());
        $this->assertSame(14, $gregorian->getDay());
    }

    public function test_it_creates_calendar_date_from_datetime(): void
    {
        /** @var CalendarManager $manager */
        $manager = $this->app->make(CalendarManager::class);

        $dateTime = CarbonImmutable::create(2024, 4, 13);

        $calendarDate = $manager->fromDateTime($dateTime, 'khmer_chhankitek');

        $this->assertSame('khmer_chhankitek', $calendarDate->getCalendar());
        $this->assertSame(2024, $calendarDate->getYear());
        $this->assertSame(13, $calendarDate->getMonth());
        $this->assertSame(1, $calendarDate->getDay());
    }
}
