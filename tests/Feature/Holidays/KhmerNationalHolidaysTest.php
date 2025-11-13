<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Holidays;

use Lisoing\Calendar\Holidays\Countries\Cambodia;
use Lisoing\Calendar\Tests\TestCase;

final class KhmerNationalHolidaysTest extends TestCase
{
    public function test_it_returns_translated_holiday_names(): void
    {
        $provider = new Cambodia;

        $holidays = $provider->holidaysForYear(2025, 'km');

        $this->assertSame(6, $holidays->count());

        $names = array_map(static fn ($holiday) => $holiday->name(), $holidays->all());

        $this->assertContains('បុណ្យចូលឆ្នាំសកល', $names);
        $this->assertContains('ទិវាជ័យជម្នះលើរបបប្រល័យពូជសាសន៍', $names);
        $this->assertContains('ទិវាស្ត្រីអន្តរជាតិ', $names);
        $this->assertContains('ទិវាពិធីបួងសួងព្រះវិញ្ញាណក្ខន្ធព្រះបរមរតនកោដ្ឋ', $names);
        $this->assertContains('បុណ្យចូលឆ្នាំខ្មែរ', $names);
        $this->assertContains('បុណ្យវិសាខបូជា', $names);
    }

    public function test_it_computes_lunar_holidays(): void
    {
        $provider = new Cambodia;

        $holidays = $provider->holidaysForYear(2025, 'en')->all();

        $dates = array_reduce($holidays, static function (array $carry, $holiday): array {
            $carry[$holiday->identifier()] = $holiday->date()->toDateString();

            return $carry;
        }, []);

        $this->assertSame('2025-04-14', $dates['khmer_new_year_2025']);
        $this->assertIsString($dates['visak_bochea_2025']);
    }
}
