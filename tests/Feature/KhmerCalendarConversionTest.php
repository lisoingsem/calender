<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature;

use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Countries\Cambodia;

final class KhmerCalendarConversionTest extends TestCase
{
    public function testSolarToLunarConversion(): void
    {
        // Test converting a known Gregorian date to Khmer lunar
        $solarDate = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');

        $lunar = Calendar::for('km')->fromCarbon($solarDate);

        $this->assertSame('km', $lunar->getCalendar());
        $this->assertSame(2025, $lunar->getYear());
        $this->assertGreaterThanOrEqual(1, $lunar->getMonth());
        $this->assertLessThanOrEqual(13, $lunar->getMonth());
        $this->assertGreaterThanOrEqual(1, $lunar->getDay());
        $this->assertLessThanOrEqual(30, $lunar->getDay());

        // Verify context data
        $context = $lunar->getContext();
        $this->assertArrayHasKey('phase', $context);
        $this->assertArrayHasKey('month_slug', $context);
        $this->assertArrayHasKey('gregorian_date', $context);
        $this->assertArrayHasKey('buddhist_era_year', $context);
    }

    public function testLunarToSolarConversion(): void
    {
        // Test converting Khmer lunar date back to Gregorian
        $solarDate = CarbonImmutable::parse('2025-04-14 00:00:00', 'Asia/Phnom_Penh');
        $lunar = Calendar::for('km')->fromCarbon($solarDate);

        // Convert back to solar
        $convertedSolar = Calendar::for('gregorian')->fromCalendar($lunar);
        $carbonDate = Calendar::for('gregorian')->toCarbon($convertedSolar);

        $this->assertSame('gregorian', $convertedSolar->getCalendar());
        $this->assertSame('2025-04-14', $carbonDate->toDateString());
    }

    public function testRoundTripConversion(): void
    {
        // Test round-trip: Solar -> Lunar -> Solar
        $originalSolar = CarbonImmutable::parse('2025-04-14 12:00:00', 'Asia/Phnom_Penh');

        // Convert to lunar
        $lunar = Calendar::for('km')->fromCarbon($originalSolar);

        // Convert back to solar
        $convertedSolar = Calendar::for('gregorian')->fromCalendar($lunar);
        $carbonDate = Calendar::for('gregorian')->toCarbon($convertedSolar);

        // Should match the original date (within same day)
        $this->assertSame($originalSolar->toDateString(), $carbonDate->toDateString());
    }

    public function testKhmerNewYearDate(): void
    {
        // Test Khmer New Year 2025 (actual date is April 13-16)
        $newYearDate = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

        $lunar = Calendar::for('km')->fromCarbon($newYearDate);

        // Verify lunar date structure
        $context = $lunar->getContext();
        $monthSlug = $context['month_slug'] ?? null;

        $this->assertNotNull($monthSlug);
        // April 14 could be in various months depending on the lunar cycle
        $validMonths = ['phalgun', 'cetra', 'visak', 'jesh'];
        $this->assertContains($monthSlug, $validMonths, "Month slug '{$monthSlug}' is not in expected months");
    }

    public function testCurrentDateConversion(): void
    {
        // Test with current date
        $now = CarbonImmutable::now('Asia/Phnom_Penh');

        $lunar = Calendar::for('km')->fromCarbon($now);
        $solar = Calendar::for('gregorian')->fromCalendar($lunar);
        $carbonDate = Calendar::for('gregorian')->toCarbon($solar);

        // Should be able to convert current date
        $this->assertSame('km', $lunar->getCalendar());
        $this->assertSame('gregorian', $solar->getCalendar());
        $this->assertSame($now->toDateString(), $carbonDate->toDateString());
    }

    public function testUsingCountryHelper(): void
    {
        // Test using Country helper
        $date = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

        $lunar = Calendar::for(Cambodia::calendar())->fromCarbon($date);

        $this->assertSame('km', $lunar->getCalendar());
        $this->assertSame(2025, $lunar->getYear());
    }

    public function testMultipleDateConversions(): void
    {
        // Test multiple dates to ensure consistency
        $testDates = [
            '2025-01-01',
            '2025-04-14', // Khmer New Year
            '2025-06-15',
            '2025-12-31',
        ];

        foreach ($testDates as $dateString) {
            $solar = CarbonImmutable::parse($dateString, 'Asia/Phnom_Penh');
            $lunar = Calendar::for('km')->fromCarbon($solar);
            $backToSolar = Calendar::for('gregorian')->fromCalendar($lunar);
            $carbonDate = Calendar::for('gregorian')->toCarbon($backToSolar);

            $this->assertSame($solar->toDateString(), $carbonDate->toDateString(), "Failed for date: {$dateString}");
        }
    }

    public function testLunarDateContext(): void
    {
        $date = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');
        $lunar = Calendar::for('km')->fromCarbon($date);
        $context = $lunar->getContext();

        // Verify all expected context keys are present
        $expectedKeys = ['phase', 'month_slug', 'gregorian_date', 'timezone', 'buddhist_era_year', 'animal_year_index', 'era_year_index', 'weekday_index'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $context, "Missing context key: {$key}");
        }

        // Verify phase is valid
        $this->assertContains($context['phase'], ['waxing', 'waning']);

        // Verify Buddhist Era year
        $this->assertGreaterThan(2500, $context['buddhist_era_year']);
    }
}

