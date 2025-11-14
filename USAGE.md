# Calendar & Holidays Usage Guide

This guide covers how to use the calendar system for date conversions and the holidays system for retrieving country-specific holidays.

## Table of Contents

1. [Calendar Usage](#calendar-usage)
   - [Basic Conversions](#basic-conversions)
   - [Calendar Types](#calendar-types)
   - [Date Formatting](#date-formatting)
   - [Date Manipulation](#date-manipulation)
   - [Calendar Switching](#calendar-switching)

2. [Holidays Usage](#holidays-usage)
   - [Getting Holidays](#getting-holidays)
   - [Working with Holiday Collections](#working-with-holiday-collections)
   - [Individual Holiday Lookup](#individual-holiday-lookup)
   - [Holiday Metadata](#holiday-metadata)
   - [Localization](#localization)

---

## Calendar Usage

### Basic Conversions

The calendar system supports converting dates between different calendar types (Solar, Lunisolar, and Lunar).

#### Using the Calendar Facade

```php
use Lisoing\Calendar;
use Carbon\CarbonImmutable;

// Parse a date string (Carbon-like API)
$lunar = Calendar::parse('2025-04-14', 'km');

// Get current date in a specific calendar
$lunar = Calendar::now('km');

// Create from year/month/day
$lunar = Calendar::create(2025, 4, 14, 'km');

// Convert Carbon to calendar date
$gregorian = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');
$lunar = Calendar::toLunar($gregorian, 'km');
```

#### Using Calendar Context (Fluent API)

```php
use Lisoing\Calendar;
use Lisoing\Countries\Cambodia;
use Carbon\CarbonImmutable;

$date = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Convert to lunisolar calendar
$lunisolar = Calendar::for(Cambodia::class)
    ->fromCarbon($date)
    ->toLunisolar();

// Get the CalendarDate object
$lunarDate = $lunisolar->getDate();

echo $lunarDate->getYear();    // 2025
echo $lunarDate->getMonth();   // 5
echo $lunarDate->getDay();     // 15
echo $lunarDate->getCalendar(); // 'km'
```

### Calendar Types

The package supports three calendar types:

#### 1. Solar Calendar (Gregorian)
- **Type**: Pure sun-based
- **Year Length**: 365 or 366 days
- **Example**: `'gregorian'`

```php
$gregorian = Calendar::for('gregorian')->fromCarbon(CarbonImmutable::now());
Calendar::isSolar('gregorian'); // true
```

#### 2. Lunisolar Calendar (Khmer, Chinese, etc.)
- **Type**: Moon + Sun with leap months
- **Year Length**: ~354-384 days (varies with leap months)
- **Example**: `'km'` (Khmer), `'chinese'`

```php
use Lisoing\Countries\Cambodia;

$lunisolar = Calendar::for(Cambodia::class)
    ->fromCarbon(CarbonImmutable::now())
    ->toLunisolar();

Calendar::isLunisolar('km'); // true
```

#### 3. Lunar Calendar (Islamic/Hijri)
- **Type**: Pure moon-based
- **Year Length**: ~354 days
- **Example**: `'islamic'`

```php
$islamic = Calendar::for('gregorian')
    ->fromCarbon(CarbonImmutable::now())
    ->toIslamic();

Calendar::isLunar('islamic'); // true
```

### Date Formatting

#### Format Day (Khmer-style)

```php
$lunar = Calendar::parse('2025-04-14', 'km');

// Get formatted day like chhankitek (១កើត, ១៤រោច)
echo $lunar->formatDay();        // '១៥កើត' (15 Keit)
echo $lunar->formatDay('km');    // '១៥កើត'
echo $lunar->formatDay('en');    // '១៥កើត'
```

#### Carbon-style Formatting

```php
$lunar = Calendar::parse('2025-04-14', 'km');

// Format with pattern
echo $lunar->format('dddd D');           // 'ច័ន្ទ 15'
echo $lunar->format('OD OM OY');         // '១៥ ៥ ២០២៥' (Khmer digits)
echo $lunar->format('LLLL YYYY');        // '១៥កើត ចេត្រ 2025'
echo $lunar->format('dddd L MMMM YYYY'); // 'ច័ន្ទ ១៥កើត ចេត្រ 2025'
```

#### Full String Representation

```php
$lunar = Calendar::parse('2025-04-14', 'km');

// Get full formatted string
echo $lunar->toString();         // 'ថ្ងៃច័ន្ទ ១៥កើត ខែចេត្រ...'
echo $lunar->toString('km');    // Khmer format with structure words
echo $lunar->toString('en');    // English format without structure words
echo $lunar->toString('en', false); // Without structure words

// Or cast to string
echo (string) $lunar;            // Same as toString()
```

#### Access Individual Components

```php
$lunar = Calendar::parse('2025-04-14', 'km');

// Basic components
echo $lunar->getYear();          // 2025
echo $lunar->getMonth();         // 5
echo $lunar->getDay();           // 15
echo $lunar->getCalendar();      // 'km'

// Formatted components (Khmer calendar)
echo $lunar->getDayOfWeek();     // 'ច័ន្ទ' (Monday)
echo $lunar->getLunarDay();      // '១៥កើត'
echo $lunar->getLunarMonth();    // 'ចេត្រ'
echo $lunar->getLunarYear();     // '២៥៦៩' (Buddhist Era)
echo $lunar->getAnimalYear();    // 'ឆ្លូវ' (Ox)
echo $lunar->getEraYear();       // 'ត្រីស័ក'
echo $lunar->getPhase();         // 'កើត' (Waxing)
```

### Date Manipulation

```php
$lunar = Calendar::parse('2025-04-14', 'km');

// Add/subtract time
$tomorrow = $lunar->addDays(1);
$nextMonth = $lunar->addMonths(1);
$nextYear = $lunar->addYears(1);
$yesterday = $lunar->subDays(1);

// Date checks
$lunar->isToday();    // Check if today
$lunar->isPast();     // Check if in past
$lunar->isFuture();   // Check if in future

// Convert to Carbon
$carbon = $lunar->toCarbon(); // Get CarbonImmutable instance
```

### Calendar Switching

You can chain calendar conversions fluently:

```php
use Lisoing\Countries\Cambodia;
use Carbon\CarbonImmutable;

$date = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Chain calendar conversions
$result = Calendar::for('gregorian')
    ->fromCarbon($date)
    ->toLunisolar('km')      // Switch to Khmer lunisolar
    ->toGregorian()          // Switch back to Gregorian
    ->toIslamic()            // Switch to Islamic lunar
    ->toString();            // Format as string

// Or get the date object
$finalDate = Calendar::for('gregorian')
    ->fromCarbon($date)
    ->toLunisolar('km')
    ->getDate();
```

---

## Holidays Usage

### Getting Holidays

#### Using HolidayManager (Direct)

```php
use Lisoing\Calendar\Holidays\HolidayManager;

$manager = app(HolidayManager::class);

// Get all holidays for a country and year
$holidays = $manager->forCountry(2025, 'KH', 'en');

// Iterate over holidays
foreach ($holidays as $holiday) {
    echo $holiday->name();           // Holiday name
    echo $holiday->date()->format('Y-m-d'); // Date
    echo $holiday->identifier();     // Unique identifier
}
```

#### Using Country Helper

```php
use Lisoing\Countries\Cambodia;
use Lisoing\Calendar\Holidays\HolidayManager;

$manager = app(HolidayManager::class);

// Using country class
$holidays = $manager->forCountry(2025, Cambodia::code(), 'en');

// Or using country code directly
$holidays = $manager->forCountry(2025, 'KH', 'en');
```

#### Using CalendarToolkit

```php
use Lisoing\Calendar\Support\CalendarToolkit;

$toolkit = CalendarToolkit::make();

// Get all holidays
$holidays = $toolkit->holidays(2025, 'KH', 'en');

// Get a specific holiday
$newYear = $toolkit->holiday('khmer_new_year', 2025, 'KH', 'en');
if ($newYear) {
    echo $newYear->name(); // "Khmer New Year"
    echo $newYear->date()->format('Y-m-d'); // "2025-04-13"
}
```

### Working with Holiday Collections

```php
use Lisoing\Calendar\Holidays\HolidayManager;

$manager = app(HolidayManager::class);
$holidays = $manager->forCountry(2025, 'KH', 'en');

// Count holidays
echo count($holidays); // Number of holidays

// Iterate
foreach ($holidays as $holiday) {
    // Process each holiday
}

// Filter holidays
$publicHolidays = [];
foreach ($holidays as $holiday) {
    $metadata = $holiday->metadata();
    if (($metadata['type'] ?? 'public') === 'public') {
        $publicHolidays[] = $holiday;
    }
}

// Find specific holiday by identifier
$newYear = null;
foreach ($holidays as $holiday) {
    if ($holiday->identifier() === 'khmer_new_year_2025') {
        $newYear = $holiday;
        break;
    }
}
```

### Individual Holiday Lookup

```php
use Lisoing\Calendar\Support\CalendarToolkit;

$toolkit = CalendarToolkit::make();

// Find holiday by slug
$newYear = $toolkit->holiday('khmer_new_year', 2025, 'KH', 'en');

if ($newYear) {
    echo $newYear->name();        // "Khmer New Year"
    echo $newYear->date()->format('Y-m-d'); // "2025-04-13"
    echo $newYear->identifier();  // "khmer_new_year_2025"
    echo $newYear->country();     // "KH"
    echo $newYear->locale();      // "en"
}
```

### Holiday Metadata

Each holiday includes metadata with additional information:

```php
$holiday = $toolkit->holiday('khmer_new_year', 2025, 'KH', 'en');

if ($holiday) {
    $metadata = $holiday->metadata();
    
    echo $metadata['type'];        // 'public' or other type
    echo $metadata['description']; // Optional description if available
    
    // Access all metadata
    foreach ($metadata as $key => $value) {
        echo "$key: $value";
    }
}
```

### Localization

Holidays are automatically translated based on the locale:

```php
use Lisoing\Calendar\Holidays\HolidayManager;

$manager = app(HolidayManager::class);

// English holidays
$holidaysEn = $manager->forCountry(2025, 'KH', 'en');
foreach ($holidaysEn as $holiday) {
    echo $holiday->name(); // "Khmer New Year"
}

// Khmer holidays
$holidaysKm = $manager->forCountry(2025, 'KH', 'km');
foreach ($holidaysKm as $holiday) {
    echo $holiday->name(); // "ចូលឆ្នាំខ្មែរ"
}
```

### Complete Example: Calendar + Holidays

```php
use Lisoing\Calendar;
use Lisoing\Calendar\Support\CalendarToolkit;
use Lisoing\Countries\Cambodia;
use Carbon\CarbonImmutable;

// Get today's date in Khmer calendar
$today = CarbonImmutable::now('Asia/Phnom_Penh');
$lunarToday = Calendar::for(Cambodia::class)
    ->fromCarbon($today)
    ->toLunisolar()
    ->getDate();

echo "Today in Khmer calendar: " . $lunarToday->toString('km');

// Get holidays for this year
$toolkit = CalendarToolkit::make();
$holidays = $toolkit->holidays($today->year, 'KH', 'en');

// Check if today is a holiday
$isHoliday = false;
$holidayName = null;

foreach ($holidays as $holiday) {
    if ($holiday->date()->isSameDay($today)) {
        $isHoliday = true;
        $holidayName = $holiday->name();
        break;
    }
}

if ($isHoliday) {
    echo "Today is a holiday: $holidayName";
} else {
    echo "Today is not a holiday";
}

// Get next holiday
$nextHoliday = null;
foreach ($holidays as $holiday) {
    if ($holiday->date()->isFuture()) {
        $nextHoliday = $holiday;
        break;
    }
}

if ($nextHoliday) {
    echo "Next holiday: " . $nextHoliday->name() . " on " . $nextHoliday->date()->format('Y-m-d');
}
```

### Returning from Controllers

```php
use Illuminate\Http\JsonResponse;
use Lisoing\Calendar;
use Lisoing\Calendar\Support\CalendarToolkit;
use Carbon\CarbonImmutable;

class CalendarController extends Controller
{
    public function lunarDate(string $date): JsonResponse
    {
        $gregorian = CarbonImmutable::parse($date, 'Asia/Phnom_Penh');
        $lunar = Calendar::for('km')->fromCarbon($gregorian)->getDate();
        
        return response()->json([
            'gregorian' => $gregorian->toDateString(),
            'lunar' => [
                'formatted_day' => $lunar->formatDay(),
                'year' => $lunar->getYear(),
                'month' => $lunar->getMonth(),
                'day' => $lunar->getDay(),
                'calendar' => $lunar->getCalendar(),
                'full_string' => $lunar->toString('en'),
            ],
        ]);
    }

    public function holidays(int $year, string $country = 'KH'): JsonResponse
    {
        $toolkit = CalendarToolkit::make();
        $holidays = $toolkit->holidays($year, $country, app()->getLocale());
        
        $holidaysArray = [];
        foreach ($holidays as $holiday) {
            $holidaysArray[] = [
                'name' => $holiday->name(),
                'date' => $holiday->date()->toDateString(),
                'identifier' => $holiday->identifier(),
                'metadata' => $holiday->metadata(),
            ];
        }
        
        return response()->json([
            'year' => $year,
            'country' => $country,
            'holidays' => $holidaysArray,
        ]);
    }
}
```

---

## Summary

### Calendar Quick Reference

| Task | Code |
|------|------|
| Parse date | `Calendar::parse('2025-04-14', 'km')` |
| Current date | `Calendar::now('km')` |
| Convert Carbon | `Calendar::for('km')->fromCarbon($carbon)` |
| Switch calendar | `->toLunisolar('km')` or `->toIslamic()` |
| Format day | `$date->formatDay()` |
| Format string | `$date->format('dddd D')` |
| Full string | `$date->toString('en')` |

### Holidays Quick Reference

| Task | Code |
|------|------|
| Get all holidays | `$manager->forCountry(2025, 'KH', 'en')` |
| Find holiday | `$toolkit->holiday('khmer_new_year', 2025, 'KH', 'en')` |
| Holiday name | `$holiday->name()` |
| Holiday date | `$holiday->date()` |
| Holiday metadata | `$holiday->metadata()` |

For more examples, see [EXAMPLES.md](EXAMPLES.md).
