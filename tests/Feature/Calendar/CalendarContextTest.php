<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Calendar;

use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Countries\Cambodia;

final class CalendarContextTest extends TestCase
{
    public function testFromCarbonCreatesLunisolarDate(): void
    {
        $date = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');

        $lunar = Calendar::for(Cambodia::calendar())->fromCarbon($date);

        $this->assertSame('km', $lunar->getCalendar());
        $this->assertSame(2025, $lunar->getYear());
    }

    public function testConvertBetweenCalendars(): void
    {
        $date = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');
        $lunar = Calendar::for(Cambodia::calendar())->fromCarbon($date);

        $gregorian = Calendar::for('gregorian')->fromCalendar($lunar);
        $carbon = Calendar::for('gregorian')->toCarbon($gregorian);

        $this->assertSame('gregorian', $gregorian->getCalendar());
        $this->assertSame('2025-04-14', $carbon->toDateString());
    }
}

