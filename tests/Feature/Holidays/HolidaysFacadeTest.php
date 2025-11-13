<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Holidays;

use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Countries\Cambodia;
use Lisoing\Holidays\Holidays;

final class HolidaysFacadeTest extends TestCase
{
    public function testItReturnsHolidayArrayUsingCountryHelper(): void
    {
        $holidays = Holidays::for(Cambodia::make(), 2025, 'en')->get();

        $names = array_column($holidays, 'name');

        $this->assertContains('Khmer New Year', $names);
    }

    public function testItAcceptsCountryCodeStrings(): void
    {
        $holidays = Holidays::for(country: 'KH', year: 2024, locale: 'en')->get();

        $this->assertNotEmpty($holidays);

        $dates = array_map(static fn (array $holiday): string => $holiday['date']->toDateString(), $holidays);

        $this->assertContains('2024-04-13', $dates);
    }
}

