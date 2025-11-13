<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Toolkit;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Facades\Toolkit;
use Lisoing\Calendar\Support\CalendarToolkit;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class CalendarToolkitTest extends TestCase
{
    public function test_it_converts_and_formats_with_toolkit(): void
    {
        /** @var CalendarToolkit $toolkit */
        $toolkit = $this->app->make(CalendarToolkit::class);

        $lunar = new CalendarDate(2025, 13, 1, 'khmer_chhankitek');

        $gregorian = $toolkit->toSolar($lunar);

        $this->assertSame('gregorian', $gregorian->getCalendar());
        $this->assertSame(2025, $gregorian->getYear());
        $this->assertSame(4, $gregorian->getMonth());
        $this->assertSame(14, $gregorian->getDay());

        $formatted = $toolkit->format($lunar, 'km');

        $this->assertStringContainsString('កើត', $formatted);
        $this->assertStringContainsString('ខែបន្ថែម', $formatted);
        $this->assertSame('2025-04-14', $toolkit->toDateTime($lunar)->toDateString());
    }

    public function test_facade_provides_holidays_when_enabled(): void
    {
        $holidays = Toolkit::holidays(2025, 'KH', 'km');

        $names = array_map(static fn ($holiday) => $holiday->name(), $holidays->all());

        $this->assertContains('បុណ្យចូលឆ្នាំខ្មែរ', $names);
        $this->assertContains('ទិវាស្ត្រីអន្តរជាតិ', $names);
    }

    public function test_it_returns_empty_collection_when_holidays_disabled(): void
    {
        config()->set('calendar.features.holidays.enabled', false);

        /** @var CalendarToolkit $toolkit */
        $toolkit = $this->app->make(CalendarToolkit::class);

        $holidays = $toolkit->holidays(2025, 'KH', 'km');

        $this->assertCount(0, $holidays);

        config()->set('calendar.features.holidays.enabled', true);
    }

    public function test_it_can_convert_from_datetime(): void
    {
        $dateTime = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');

        $lunar = Toolkit::toLunar($dateTime);

        $this->assertSame('khmer_chhankitek', $lunar->getCalendar());
        $this->assertSame('keit', $lunar->getContextValue('phase'));
    }

    public function test_it_can_lookup_holiday_helpers(): void
    {
        $holiday = Toolkit::holiday('khmer_new_year', 2025, 'KH', 'en');

        $this->assertNotNull($holiday);
        $this->assertSame('Khmer New Year', $holiday->name());
        $this->assertSame('2025-04-14', $holiday->date()->toDateString());

        $dates = Toolkit::holidayDates(2025, 'KH', 'en');

        $this->assertTrue($dates->has('khmer_new_year_2025'));
        $this->assertSame('2025-04-14', $dates->get('khmer_new_year_2025')->toDateString());
    }
}

