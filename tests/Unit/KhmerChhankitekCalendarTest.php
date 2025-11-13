<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Unit;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Calendars\Khmer\KhmerChhankitekCalendar;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class KhmerChhankitekCalendarTest extends TestCase
{
    public function test_it_converts_lunar_to_gregorian(): void
    {
        $calendar = $this->makeCalendar();

        $date = new CalendarDate(2025, 13, 1, 'khmer_chhankitek');

        $gregorian = $calendar->toDateTime($date);

        $this->assertSame('2025-04-14', $gregorian->toDateString());
    }

    public function test_it_enriches_context_from_gregorian_date(): void
    {
        $calendar = $this->makeCalendar();

        $gregorian = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

        $calendarDate = $calendar->fromDateTime($gregorian);

        $this->assertSame('khmer_chhankitek', $calendarDate->getCalendar());
        $this->assertSame(13, $calendarDate->getMonth());
        $this->assertSame(1, $calendarDate->getDay());
        $this->assertSame('keit', $calendarDate->getContextValue('phase'));
        $this->assertArrayHasKey('buddhist_era_year', $calendarDate->getContext());
    }

    private function makeCalendar(): KhmerChhankitekCalendar
    {
        $calendar = new KhmerChhankitekCalendar();
        $calendar->configure(config('calendar.calendar_settings.khmer_chhankitek') ?? []);

        return $calendar;
    }
}

