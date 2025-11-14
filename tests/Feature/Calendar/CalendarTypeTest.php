<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Calendar;

use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Countries\Cambodia;

final class CalendarTypeTest extends TestCase
{
    public function testCalendarTypeDetection(): void
    {
        // Test solar calendar
        $this->assertTrue(Calendar::isSolar('gregorian'));
        $this->assertFalse(Calendar::isLunar('gregorian'));
        $this->assertFalse(Calendar::isLunisolar('gregorian'));

        // Test lunisolar calendar
        $this->assertTrue(Calendar::isLunisolar('km'));
        $this->assertFalse(Calendar::isLunar('km'));
        $this->assertFalse(Calendar::isSolar('km'));

        // Test lunar calendar
        $this->assertTrue(Calendar::isLunar('islamic'));
        $this->assertFalse(Calendar::isLunisolar('islamic'));
        $this->assertFalse(Calendar::isSolar('islamic'));
    }

    public function testToLunisolarMethod(): void
    {
        $date = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');

        // Convert to lunisolar using country
        $lunisolarContext = Calendar::for(Cambodia::class)->fromCarbon($date)->toLunisolar();
        $lunisolar = $lunisolarContext->getDate();

        $this->assertSame('km', $lunisolar->getCalendar());
        $this->assertTrue(Calendar::isLunisolar($lunisolar->getCalendar()));
    }

    public function testToIslamicMethod(): void
    {
        if (! class_exists('IntlDateFormatter')) {
            $this->markTestSkipped('Intl extension is required for Islamic calendar');
        }

        $date = CarbonImmutable::parse('2025-04-14 00:00:00', 'UTC');

        // Convert to Islamic calendar
        $islamicContext = Calendar::for('gregorian')->fromCarbon($date)->toIslamic();
        $islamic = $islamicContext->getDate();

        $this->assertSame('islamic', $islamic->getCalendar());
        $this->assertTrue(Calendar::isLunar($islamic->getCalendar()));
    }

    public function testToGregorianMethod(): void
    {
        $date = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');

        // Convert to lunisolar first
        $lunisolarContext = Calendar::for('km')->fromCarbon($date);
        
        // Then convert to gregorian
        $gregorianContext = $lunisolarContext->toGregorian();
        $gregorian = $gregorianContext->getDate();

        $this->assertSame('gregorian', $gregorian->getCalendar());
        $this->assertTrue(Calendar::isSolar($gregorian->getCalendar()));
    }

    public function testCalendarSwitchingChain(): void
    {
        if (! class_exists('IntlDateFormatter')) {
            $this->markTestSkipped('Intl extension is required for Islamic calendar');
        }

        $date = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');

        // Chain: Gregorian -> Lunisolar -> Gregorian -> Islamic
        $result = Calendar::for('gregorian')
            ->fromCarbon($date)
            ->toLunisolar('km')
            ->toGregorian()
            ->toIslamic()
            ->getDate();

        $this->assertSame('islamic', $result->getCalendar());
        $this->assertTrue(Calendar::isLunar($result->getCalendar()));
    }
}

