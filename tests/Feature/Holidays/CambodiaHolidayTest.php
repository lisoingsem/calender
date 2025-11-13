<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Holidays;

use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\Support\Khmer\LunisolarCalculator;
use Lisoing\Calendar\Tests\TestCase;

final class CambodiaHolidayTest extends TestCase
{
    public function testKhmerNewYearIsCalculatedFromLunisolarCalendar(): void
    {
        /** @var HolidayManager $manager */
        $manager = $this->app->make(HolidayManager::class);

        $holidays = $manager->forCountry(2025, 'KH', 'en');

        $khmerNewYear = null;

        foreach ($holidays as $holiday) {
            if ($holiday->identifier() === 'khmer_new_year_2025') {
                $khmerNewYear = $holiday;
                break;
            }
        }

        $this->assertNotNull($khmerNewYear, 'Khmer New Year holiday not found.');
        $this->assertSame('Khmer New Year', $khmerNewYear->name());

        $calculator = new LunisolarCalculator();
        $expectedDate = $calculator->getKhmerNewYearDate(2025)->toDateString();

        $this->assertSame($expectedDate, $khmerNewYear->date()->toDateString());
    }

    public function testVisakBocheaFollowsLunarPhaseRule(): void
    {
        /** @var HolidayManager $manager */
        $manager = $this->app->make(HolidayManager::class);

        $holidays = $manager->forCountry(2024, 'KH', 'en');

        $visak = null;

        foreach ($holidays as $holiday) {
            if ($holiday->identifier() === 'visak_bochea_2024') {
                $visak = $holiday;
                break;
            }
        }

        $this->assertNotNull($visak, 'Visak Bochea holiday not found.');

        $calculator = new LunisolarCalculator();

        $expected = $calculator
            ->toSolar(2024, 'visak', 15, 'waxing')
            ->toDateString();

        $this->assertSame($expected, $visak->date()->toDateString());
    }
}

