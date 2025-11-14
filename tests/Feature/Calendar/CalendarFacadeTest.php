<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Calendar;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Facades\Calendar;
use Lisoing\Calendar\Tests\TestCase;

final class CalendarFacadeTest extends TestCase
{
    public function testToLunarAndToSolarHelpers(): void
    {
        $gregorian = CarbonImmutable::create(2025, 4, 14, 0, 0, 0, 'Asia/Phnom_Penh');

        $lunarDate = Calendar::toLunar($gregorian, 'km');

        $this->assertSame('km', $lunarDate->getCalendar());

        $backToGregorian = Calendar::toSolar($lunarDate, 'gregorian');

        $this->assertSame($gregorian->toDateString(), $backToGregorian->toDateString());
    }
}

