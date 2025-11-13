<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Holidays;

use Lisoing\Calendar\Holidays\Countries\KH\KhmerNationalHolidays;
use Lisoing\Calendar\Tests\TestCase;

final class KhmerNationalHolidaysTest extends TestCase
{
    public function test_it_returns_translated_holiday_names(): void
    {
        $provider = new KhmerNationalHolidays;

        $holidays = $provider->holidaysForYear(2025, 'km');

        $this->assertSame(2, $holidays->count());

        $names = array_map(static fn ($holiday) => $holiday->name(), $holidays->all());

        $this->assertContains('បុណ្យចូលឆ្នាំខ្មែរ', $names);
        $this->assertContains('បុណ្យវិសាខបូជា', $names);
    }
}
