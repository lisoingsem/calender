# Calendar Usage Examples

## Common Use Cases

### 1. Convert Gregorian (Solar) to Khmer Lunar - SIMPLEST WAY

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;

$gregorian = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Convert to lunar - ONE LINE!
$lunar = Calendar::toLunar($gregorian, 'km');

// Get formatted day like chhankitek (១កើត, ១៤រោច)
echo $lunar->formatDay();        // '១៥កើត' (15 Keit)
echo $lunar->formatDay('km');    // '១៥កើត'
echo $lunar->formatDay('en');    // '១៥កើត' (uses calendar's default locale)

// Or access individual parts
echo $lunar->getYear();          // 2025
echo $lunar->getMonth();         // 5
echo $lunar->getDay();           // 15
echo $lunar->getCalendar();      // 'km'

// Get formatted components (like chhankitek)
echo $lunar->getDayOfWeek();     // 'ច័ន្ទ' (Monday)
echo $lunar->getLunarDay();      // '១៥កើត'
echo $lunar->getLunarMonth();    // 'ចេត្រ'
echo $lunar->getLunarYear();     // '២៥៦៩' (Buddhist Era)
echo $lunar->getAnimalYear();    // 'ឆ្លូវ' (Ox)
echo $lunar->getEraYear();       // 'ត្រីស័ក'
echo $lunar->getPhase();         // 'កើត' (Waxing)

// Carbon-style formatting
echo $lunar->format('dddd D');           // 'ច័ន្ទ 15'
echo $lunar->format('OD OM OY');          // '១៥ ៥ ២០២៥' (Khmer digits)
echo $lunar->format('LLLL YYYY');         // '១៥កើត ចេត្រ 2025'
echo $lunar->format('dddd L MMMM YYYY');  // 'ច័ន្ទ ១៥កើត ចេត្រ 2025'

// Full formatted string (like chhankitek)
echo $lunar->toString();         // 'ថ្ងៃច័ន្ទ ១៥កើត ខែចេត្រ...'
echo (string) $lunar;            // Same as toString()
```

### 2. Convert Lunar back to Gregorian (Solar)

```php
// You have a lunar date (CalendarDate object)
$lunar = Calendar::toLunar($gregorian, 'km');

// Method 1: Using Calendar::toSolar() - SIMPLEST
$carbon = Calendar::toSolar($lunar, 'gregorian');

// Method 2: Using Calendar::for() context
$solarDate = Calendar::for('gregorian')->fromCalendar($lunar);
$carbon = Calendar::for('gregorian')->toCarbon($solarDate);

// Result is Carbon instance
echo $carbon->toDateString(); // '2025-04-14'
```

### 3. Complete Round-Trip Example

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;

$original = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Convert to lunar
$lunar = Calendar::toLunar($original, 'km');

// Convert back to solar
$backToSolar = Calendar::toSolar($lunar, 'gregorian');

// Should match original
echo $backToSolar->toDateString(); // '2025-04-14'
```

### 4. Get Lunar Date Information - EASY FORMAT

```php
$gregorian = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');
$lunar = Calendar::toLunar($gregorian, 'km');

// Get formatted day like chhankitek - EASIEST!
echo $lunar->formatDay();  // '១៥កើត' (like chhankitek)

// Or get individual parts
$year = $lunar->getYear();   // 2025
$month = $lunar->getMonth(); // 5
$day = $lunar->getDay();     // 15

// Extended context
$context = $lunar->getContext();
echo $context['phase'];              // 'waxing' or 'waning'
echo $context['month_slug'];         // 'cetra', 'visak', etc.
echo $context['buddhist_era_year']; // 2569
echo $context['animal_year_index'];  // 0-11
echo $context['era_year_index'];     // 0-9
```

### 5. Working with Current Date

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;

$now = CarbonImmutable::now('Asia/Phnom_Penh');

// Convert current date to lunar
$lunar = Calendar::toLunar($now, 'km');

// Get info
echo "Today in Khmer calendar: ";
echo "Year {$lunar->getYear()}, Month {$lunar->getMonth()}, Day {$lunar->getDay()}";
```

## Important Notes

### ❌ WRONG - Don't call toLunar() on Carbon

```php
$carbon = CarbonImmutable::parse('2025-04-14');
$lunar = $carbon->toLunar(); // ❌ ERROR: Method doesn't exist on Carbon
```

### ✅ CORRECT - Call toLunar() on Calendar

```php
$carbon = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');
$lunar = Calendar::toLunar($carbon, 'km'); // ✅ CORRECT
```

## Method Reference

### Calendar Facade Methods

```php
// Convert Carbon to Lunar
Calendar::toLunar(CarbonInterface $dateTime, ?string $calendarIdentifier = 'km')

// Convert Lunar to Solar (returns Carbon)
Calendar::toSolar(CalendarDate $date, string $targetIdentifier = 'gregorian')

// Get calendar context
Calendar::for(string $calendarIdentifier) // Returns CalendarContext

// Direct conversion
Calendar::fromDateTime(CarbonInterface $dateTime, ?string $calendarIdentifier = null)
Calendar::toDateTime(CalendarDate $date)
```

### CalendarContext Methods (from Calendar::for())

```php
$context = Calendar::for('km');

$context->fromCarbon(CarbonInterface $dateTime)  // Convert Carbon to CalendarDate
$context->fromCalendar(CalendarDate $date)      // Convert CalendarDate to CalendarDate
$context->toCarbon(CalendarDate $date)         // Convert CalendarDate to Carbon
```

## Returning CalendarDate from Controllers

### ❌ WRONG - Don't return CalendarDate directly

```php
// This will cause TypeError
return Calendar::toLunar($date, 'km');
```

### ✅ CORRECT - Convert to array or use response helper

```php
use Lisoing\Calendar;
use Carbon\CarbonImmutable;

// Method 1: Return as array (Laravel auto-converts to JSON)
$lunar = Calendar::toLunar($date, 'km');
return $lunar->toArray();

// Method 2: Return as JSON response
return response()->json([
    'lunar' => $lunar->toArray(),
    'gregorian' => $date->toDateString(),
]);

// Method 3: Return formatted string
$lunar = Calendar::toLunar($date, 'km');
return "Year: {$lunar->getYear()}, Month: {$lunar->getMonth()}, Day: {$lunar->getDay()}";

// Method 4: Return Carbon date (if you need the original date)
$lunar = Calendar::toLunar($date, 'km');
$carbon = Calendar::toSolar($lunar, 'gregorian');
return $carbon->toDateString();
```

### Complete Controller Example - SIMPLE!

```php
use Illuminate\Http\JsonResponse;
use Carbon\CarbonImmutable;
use Lisoing\Calendar;

public function getLunarDate(string $date): JsonResponse
{
    $gregorian = CarbonImmutable::parse($date, 'Asia/Phnom_Penh');
    $lunar = Calendar::toLunar($gregorian, 'km');
    
    return response()->json([
        'gregorian' => $gregorian->toDateString(),
        'lunar' => [
            'formatted_day' => $lunar->formatDay(),  // '១៥កើត' - like chhankitek!
            'year' => $lunar->getYear(),
            'month' => $lunar->getMonth(),
            'day' => $lunar->getDay(),
            'calendar' => $lunar->getCalendar(),
        ],
        'context' => $lunar->getContext(),
    ]);
}

// Or even simpler - return array directly
public function getLunarDateSimple(string $date): array
{
    $lunar = Calendar::toLunar(
        CarbonImmutable::parse($date, 'Asia/Phnom_Penh'),
        'km'
    );
    
    return [
        'day' => $lunar->formatDay(),  // '១៥កើត'
        'month' => $lunar->getContext()['month_slug'],
        'year' => $lunar->getYear(),
    ];
}
```

## Quick Reference

| What you have | What you want | How to do it |
|---------------|---------------|--------------|
| Carbon (Gregorian) | Lunar (CalendarDate) | `Calendar::toLunar($carbon, 'km')` |
| Lunar (CalendarDate) | Carbon (Gregorian) | `Calendar::toSolar($lunar, 'gregorian')` |
| Carbon | Lunar info | `Calendar::toLunar($carbon, 'km')->getContext()` |
| Lunar | Carbon | `Calendar::for('gregorian')->toCarbon($lunar)` |
| Lunar | Array (for API) | `$lunar->toArray()` |

