# Creating a New Lunisolar Calendar

This guide will help you create a new lunisolar calendar implementation for your country.

## Quick Start

1. **Extend `AbstractLunisolarCalendar`**
2. **Implement the required abstract methods**
3. **Create a calculator class** (if needed)
4. **Register your calendar**

## Step-by-Step Guide

### 1. Create Your Calendar Class

Create a new file: `src/Calendars/YourCountryCalendar.php`

```php
<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Support\YourCountry\YourCalculator;
use Lisoing\Calendar\Support\YourCountry\YourConstants;

final class YourCountryCalendar extends AbstractLunisolarCalendar
{
    private ?YourCalculator $calculator = null;

    public function __construct(?YourCalculator $calculator = null)
    {
        parent::__construct(YourConstants::TIMEZONE);
        $this->calculator = $calculator;
    }

    public function identifier(): string
    {
        return 'your_code'; // e.g., 'np' for Nepal
    }

    protected function getCalculator(): object
    {
        if ($this->calculator === null) {
            $this->calculator = new YourCalculator();
        }

        return $this->calculator;
    }

    protected function getDefaultTimezone(): string
    {
        return YourConstants::TIMEZONE; // e.g., 'Asia/Kathmandu'
    }

    protected function getMonthSlug(int $monthIndex): string
    {
        return YourConstants::lunarMonthSlug($monthIndex);
    }

    protected function getMonthIndex(string $monthSlug): int
    {
        $months = YourConstants::lunarMonths();
        
        if (! array_key_exists($monthSlug, $months)) {
            throw new RuntimeException(sprintf('Unknown lunar month slug [%s].', $monthSlug));
        }

        return $months[$monthSlug];
    }

    protected function buildContext(CarbonImmutable $dateTime, object $lunarData): array
    {
        // Add any custom context data here
        // This data will be available in CalendarDate->getContext()
        
        return [
            // Add your custom context keys here
            // 'custom_key' => $value,
        ];
    }

    protected function extractMonthSlug(object $lunarData): string
    {
        // Extract month slug from your calculator's return value
        // Example: return $lunarData->monthSlug();
        return '';
    }

    protected function extractDay(object $lunarData): int
    {
        // Extract day from your calculator's return value
        // Example: return $lunarData->day();
        return 1;
    }

    protected function extractPhase(object $lunarData): string
    {
        // Extract phase ('waxing' or 'waning') from your calculator
        // Example: return $lunarData->phase();
        return 'waxing';
    }
}
```

### 2. Create Your Calculator Class

Your calculator should have two main methods:

```php
<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\YourCountry;

use Carbon\CarbonImmutable;

class YourCalculator
{
    /**
     * Convert gregorian date to lunar date.
     *
     * @param  CarbonImmutable  $date  Gregorian date
     * @return object Your lunar date object
     */
    public function toLunar(CarbonImmutable $date): object
    {
        // Your calculation logic here
        // Return an object with methods to extract:
        // - month slug
        // - day
        // - phase (waxing/waning)
        // - any other data you need
    }

    /**
     * Convert lunar date to gregorian date.
     *
     * @param  int  $gregorianYear  Gregorian year
     * @param  string  $monthSlug  Month slug
     * @param  int  $phaseDay  Phase day (1-15)
     * @param  string  $phase  Phase ('waxing' or 'waning')
     * @return CarbonImmutable Gregorian date
     */
    public function toSolar(int $gregorianYear, string $monthSlug, int $phaseDay, string $phase): CarbonImmutable
    {
        // Your calculation logic here
        // Find the gregorian date that corresponds to the lunar date
    }
}
```

### 3. Create Constants Class (Optional)

If you have month names, day names, etc., create a constants class:

```php
<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\YourCountry;

final class YourConstants
{
    public const TIMEZONE = 'Asia/YourCity';

    public static function lunarMonthSlug(int $index): string
    {
        $months = self::lunarMonths();
        
        if (! isset($months[$index])) {
            throw new RuntimeException(sprintf('Unknown month index [%d].', $index));
        }

        return $months[$index];
    }

    /**
     * @return array<string, int> Month slug => index
     */
    public static function lunarMonths(): array
    {
        return [
            'month1' => 0,
            'month2' => 1,
            // ... etc
        ];
    }
}
```

### 4. Register Your Calendar

Create or update `src/Countries/YourCountry.php`:

```php
<?php

declare(strict_types=1);

namespace Lisoing\Countries;

use Lisoing\Calendar\Calendars\YourCountryCalendar;

final class YourCountry extends Country
{
    protected const CALENDAR = 'your_code';

    public static function defaultLocale(): ?string
    {
        return 'your_locale'; // e.g., 'ne' for Nepal
    }

    protected static function providerClass(): string
    {
        return \Lisoing\Calendar\Holidays\Countries\YourCountry::class;
    }

    protected static function countryCode(): string
    {
        return 'NP'; // ISO 3166 alpha-2 code
    }

    public static function calendarClass(): string
    {
        return YourCountryCalendar::class;
    }
}
```

## What the Abstract Class Handles

The `AbstractLunisolarCalendar` class automatically handles:

- ✅ Timezone configuration
- ✅ Date conversion boilerplate
- ✅ Context data management
- ✅ Month/phase resolution
- ✅ Stored gregorian date caching

## What You Need to Implement

You only need to implement:

1. **Calculator logic** - The actual lunisolar calculations
2. **Month mapping** - How months are named/indexed
3. **Data extraction** - How to get data from your calculator
4. **Context building** - Any additional context data

## Example: Cambodia Calendar

See `src/Calendars/CambodiaCalendar.php` for a complete example.

## Testing Your Calendar

```php
use Lisoing\Calendar;
use Carbon\CarbonImmutable;

$date = CarbonImmutable::parse('2025-04-14');
$lunar = Calendar::for('your_code')->fromCarbon($date);

echo $lunar->getYear();   // 2025
echo $lunar->getMonth();  // 5
echo $lunar->getDay();    // 15
```

## Common Patterns

### Simple Calculator Interface

If your calculator returns a simple object:

```php
protected function extractMonthSlug(object $lunarData): string
{
    return $lunarData->monthSlug;
}

protected function extractDay(object $lunarData): int
{
    return $lunarData->day;
}

protected function extractPhase(object $lunarData): string
{
    return $lunarData->phase;
}
```

### Complex Calculator Interface

If your calculator returns a complex object:

```php
protected function extractMonthSlug(object $lunarData): string
{
    return $lunarData->getMonth()->getSlug();
}

protected function extractDay(object $lunarData): int
{
    return $lunarData->getDay()->getNumber();
}

protected function extractPhase(object $lunarData): string
{
    return $lunarData->getDay()->getPhase();
}
```

## Need Help?

- Check `CambodiaCalendar.php` for a complete example
- Look at `LunisolarCalculator.php` for calculation patterns
- See `LunisolarConstants.php` for constant management

## Contributing

When you're ready to contribute:

1. Fork the repository
2. Create your calendar implementation
3. Add tests
4. Submit a PR

The abstract base class makes it much easier to add new lunisolar calendars!

