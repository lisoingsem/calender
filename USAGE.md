# Calendar Package Usage Guide

## Installation

```bash
composer require lisoing/calendar
```

## Basic Calendar Conversions

### Convert Solar (Gregorian) to Lunar (Khmer)

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Countries\Cambodia;

// Convert a Gregorian date to Khmer lunar calendar
$solarDate = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Method 1: Using calendar identifier 'km'
$lunar = Calendar::for('km')->fromCarbon($solarDate);

// Method 2: Using country helper
$lunar = Calendar::for(Cambodia::calendar())->fromCarbon($solarDate);

// Access lunar date information
echo $lunar->getYear();        // 2025
echo $lunar->getMonth();        // 5 (lunar month)
echo $lunar->getDay();          // 15 (lunar day)
echo $lunar->getCalendar();     // 'km'

// Get additional context
$context = $lunar->getContext();
echo $context['phase'];              // 'waxing' or 'waning'
echo $context['month_slug'];         // 'cetra', 'visak', etc.
echo $context['buddhist_era_year']; // 2569
echo $context['animal_year_index'];  // 0-11
```

### Convert Lunar back to Solar

```php
// Convert lunar date back to Gregorian
$solar = Calendar::for('gregorian')->fromCalendar($lunar);
$carbonDate = Calendar::for('gregorian')->toCarbon($solar);

echo $carbonDate->toDateString(); // '2025-04-14'
```

### Round-trip Conversion

```php
$original = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Convert to lunar
$lunar = Calendar::for('km')->fromCarbon($original);

// Convert back to solar
$backToSolar = Calendar::for('gregorian')->fromCalendar($lunar);
$carbon = Calendar::for('gregorian')->toCarbon($backToSolar);

// Should match original date
echo $carbon->toDateString(); // '2025-04-14'
```

## Using the Calendar Facade

### Direct Methods

```php
use Lisoing\Calendar;
use Carbon\CarbonImmutable;

$date = CarbonImmutable::now('Asia/Phnom_Penh');

// Convert to lunar
$lunar = Calendar::toLunar($date, 'km');

// Convert lunar to solar
$solar = Calendar::toSolar($lunar, 'gregorian');

// Get Carbon instance
$carbon = Calendar::toDateTime($lunar);
```

### Using Calendar Context

```php
use Lisoing\Calendar;

// Create a context for a specific calendar
$context = Calendar::for('km');

// Convert from Carbon
$lunar = $context->fromCarbon($date);

// Convert to Carbon
$carbon = $context->toCarbon($lunar);

// Convert between calendars
$gregorian = Calendar::for('gregorian')->fromCalendar($lunar);
```

## Working with Holidays

### Get All Holidays for a Year

```php
use Lisoing\Holidays\Holidays;
use Lisoing\Countries\Cambodia;

// Method 1: Using country helper
$holidays = Holidays::for(Cambodia::holiday(), year: 2025, locale: 'en')->get();

// Method 2: Using country code
$holidays = Holidays::for('KH', year: 2025, locale: 'en')->get();

// Method 3: Using country instance
$holidays = Holidays::for(Cambodia::make(), year: 2025, locale: 'en')->get();

// Iterate over holidays
foreach ($holidays as $holiday) {
    echo $holiday->name();           // 'Khmer New Year'
    echo $holiday->date()->format('Y-m-d'); // '2025-04-14'
    echo $holiday->identifier();     // 'khmer_new_year_2025'
}
```

### Get Specific Holiday

```php
use Lisoing\Calendar\Facades\Toolkit;

// Get a specific holiday by slug
$newYear = Toolkit::holiday('khmer_new_year', 2025, 'KH', 'en');

if ($newYear) {
    echo $newYear->name();  // 'Khmer New Year'
    echo $newYear->date()->format('Y-m-d');
}
```

### Get Holiday Dates as Collection

```php
use Lisoing\Calendar\Facades\Toolkit;

$holidayDates = Toolkit::holidayDates(2025, 'KH', 'en');

// Returns Collection with slug => CarbonImmutable
foreach ($holidayDates as $slug => $date) {
    echo "{$slug}: {$date->format('Y-m-d')}\n";
}
```

## Formatting Lunar Dates

```php
use Lisoing\Calendar\Facades\Toolkit;
use Carbon\CarbonImmutable;

$date = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');
$lunar = Calendar::for('km')->fromCarbon($date);

// Format lunar date (requires formatter)
$formatted = Toolkit::format($lunar, 'km');
echo $formatted; // Formatted Khmer lunar date string
```

## Current Date Conversions

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;

// Get current date in lunar calendar
$now = CarbonImmutable::now('Asia/Phnom_Penh');
$lunar = Calendar::for('km')->fromCarbon($now);

echo "Today in Khmer calendar: ";
echo "Year {$lunar->getYear()}, Month {$lunar->getMonth()}, Day {$lunar->getDay()}";

// Get context information
$context = $lunar->getContext();
echo "Buddhist Era: {$context['buddhist_era_year']}";
echo "Animal Year: {$context['animal_year_index']}";
```

## Advanced Usage

### Check Available Calendars

```php
use Lisoing\Calendar\Facades\Toolkit;

$calendars = Toolkit::calendars();
// Returns Collection: ['gregorian', 'km']
```

### Check if Holidays are Enabled

```php
use Lisoing\Calendar\Facades\Toolkit;

if (Toolkit::isHolidaysEnabled()) {
    $holidays = Toolkit::holidays(2025, 'KH', 'en');
}
```

### Using Country Helpers

```php
use Lisoing\Countries\Cambodia;

// Get country code
echo Cambodia::code(); // 'KH'

// Get calendar identifier
echo Cambodia::calendar(); // 'km'

// Get default locale
echo Cambodia::defaultLocale(); // 'km'

// Get holiday provider
$provider = Cambodia::holiday();
```

## Complete Example: Khmer New Year Information

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Holidays\Holidays;
use Lisoing\Countries\Cambodia;

// Get Khmer New Year date
$newYearDate = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Convert to lunar
$lunar = Calendar::for('km')->fromCarbon($newYearDate);
$context = $lunar->getContext();

echo "Khmer New Year 2025\n";
echo "Gregorian: {$newYearDate->format('Y-m-d')}\n";
echo "Lunar: Year {$lunar->getYear()}, Month {$lunar->getMonth()}, Day {$lunar->getDay()}\n";
echo "Month: {$context['month_slug']}\n";
echo "Phase: {$context['phase']}\n";
echo "Buddhist Era: {$context['buddhist_era_year']}\n";

// Get holiday information
$holidays = Holidays::for(Cambodia::holiday(), year: 2025, locale: 'en')->get();
$newYearHoliday = $holidays->first(fn($h) => str_contains($h->identifier(), 'khmer_new_year'));

if ($newYearHoliday) {
    echo "Holiday Name: {$newYearHoliday->name()}\n";
    echo "Date: {$newYearHoliday->date()->format('Y-m-d')}\n";
}
```

## Timezone Considerations

Always specify timezone when working with dates:

```php
use Carbon\CarbonImmutable;

// Good: Specify timezone
$date = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Also good: Set timezone explicitly
$date = CarbonImmutable::create(2025, 4, 14, 0, 0, 0, 'Asia/Phnom_Penh');
```

## Error Handling

```php
use Lisoing\Calendar;
use Lisoing\Calendar\Exceptions\CalendarNotFoundException;

try {
    $lunar = Calendar::for('invalid_calendar')->fromCarbon($date);
} catch (CalendarNotFoundException $e) {
    echo "Calendar not found: " . $e->getMessage();
}
```

