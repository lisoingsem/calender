<?php

declare(strict_types=1);

/**
 * Simple test script to verify Khmer calendar conversions.
 * 
 * Usage in Laravel Tinker:
 * php artisan tinker
 * >>> require base_path('vendor/lisoing/calendar/tests/Feature/TestCalendarConversion.php');
 * >>> testCalendarConversion();
 * 
 * Or run directly:
 * php -r "require 'vendor/autoload.php'; \$app = require 'vendor/orchestra/testbench-core/laravel/bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); require 'tests/Feature/TestCalendarConversion.php'; testCalendarConversion();"
 */

use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Countries\Cambodia;

function testCalendarConversion(): void
{
    echo "\n=== Khmer Calendar Conversion Test ===\n\n";

    // Test 1: Current date
    echo "1. Current Date Conversion:\n";
    $now = CarbonImmutable::now('Asia/Phnom_Penh');
    echo "   Solar (Gregorian): {$now->format('Y-m-d H:i:s')}\n";
    
    $lunar = Calendar::for('km')->fromCarbon($now);
    $context = $lunar->getContext();
    echo "   Lunar: Year {$lunar->getYear()}, Month {$lunar->getMonth()}, Day {$lunar->getDay()}\n";
    echo "   Phase: {$context['phase']}, Month: {$context['month_slug']}\n";
    echo "   Buddhist Era: {$context['buddhist_era_year']}\n";
    
    $backToSolar = Calendar::for('gregorian')->fromCalendar($lunar);
    $carbonDate = Calendar::for('gregorian')->toCarbon($backToSolar);
    echo "   Back to Solar: {$carbonDate->format('Y-m-d')}\n";
    echo "   ✓ Round-trip successful: " . ($now->toDateString() === $carbonDate->toDateString() ? 'YES' : 'NO') . "\n\n";

    // Test 2: Khmer New Year 2025
    echo "2. Khmer New Year 2025:\n";
    $newYear = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');
    echo "   Solar: {$newYear->format('Y-m-d')}\n";
    
    $lunar = Calendar::for('km')->fromCarbon($newYear);
    $context = $lunar->getContext();
    echo "   Lunar: Year {$lunar->getYear()}, Month {$lunar->getMonth()}, Day {$lunar->getDay()}\n";
    echo "   Phase: {$context['phase']}, Month: {$context['month_slug']}\n";
    echo "   Buddhist Era: {$context['buddhist_era_year']}\n\n";

    // Test 3: Using Country helper
    echo "3. Using Country Helper:\n";
    $date = CarbonImmutable::parse('2025-06-15', 'Asia/Phnom_Penh');
    echo "   Solar: {$date->format('Y-m-d')}\n";
    
    $lunar = Calendar::for(Cambodia::calendar())->fromCarbon($date);
    $context = $lunar->getContext();
    echo "   Lunar: Year {$lunar->getYear()}, Month {$lunar->getMonth()}, Day {$lunar->getDay()}\n";
    echo "   Phase: {$context['phase']}, Month: {$context['month_slug']}\n\n";

    // Test 4: Multiple dates
    echo "4. Multiple Date Conversions:\n";
    $testDates = ['2025-01-01', '2025-04-14', '2025-06-15', '2025-12-31'];
    
    foreach ($testDates as $dateString) {
        $solar = CarbonImmutable::parse($dateString, 'Asia/Phnom_Penh');
        $lunar = Calendar::for('km')->fromCarbon($solar);
        $backToSolar = Calendar::for('gregorian')->fromCalendar($lunar);
        $carbonDate = Calendar::for('gregorian')->toCarbon($backToSolar);
        
        $match = $solar->toDateString() === $carbonDate->toDateString() ? '✓' : '✗';
        echo "   {$match} {$dateString} → Lunar → {$carbonDate->format('Y-m-d')}\n";
    }

    echo "\n=== Test Complete ===\n";
}

if (php_sapi_name() === 'cli' && !defined('LARAVEL_START')) {
    // Can be run directly for testing
    testCalendarConversion();
}

