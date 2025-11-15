# calendar

Universal lunar and solar calendar package for Laravel and PHP, maintained by a global community. It brings worldwide holiday data, multilingual translations, and a welcoming contribution workflow inspired by the best open-source practices.

## Highlights

- 🔭 **Three Calendar Types**: Support for Lunar (Islamic/Hijri), Lunisolar (Khmer, Chinese, etc.), and Solar (Gregorian) calendars
- 🏮 Holiday providers organised per country with translation-ready labels
- 🌍 Localization files ready for contributors (`resources/lang/{locale}/holidays.php`)
- ⚙️ Laravel-first experience with auto-discovery, service provider, and facade
- ✅ PSR-12 compliant codebase with Laravel Pint, PHPUnit, and PHPStan

## Installation

```bash
composer require lisoing/calendar
```

## Creating New Lunisolar Calendars

Want to add support for your country's lunisolar calendar? We've made it easy!

### Quick Start

1. **Extend `AbstractLunisolarCalendar`** - Handles all the boilerplate
2. **Create a calculator class** - Just implement `toLunar()` and `toSolar()`
3. **Register your calendar** - Add it to your Country class

### Documentation

- 📖 **[Complete Guide](docs/CREATING_LUNISOLAR_CALENDAR.md)** - Step-by-step instructions
- 📝 **[Calendar Template](docs/templates/LunisolarCalendarTemplate.php)** - Copy and modify
- 📝 **[Calculator Template](docs/templates/CalculatorTemplate.php)** - Calculator template

### Example

```php
// Just extend AbstractLunisolarCalendar and implement a few methods
final class NepalCalendar extends AbstractLunisolarCalendar
{
    // Implement 8 simple methods - that's it!
    // The abstract class handles all the complex date conversion logic
}
```

See `CambodiaCalendar.php` for a complete working example.

## Quick Start

📖 **For detailed usage examples, see [USAGE.md](USAGE.md)**

### Holiday lookups (Spatie-style)

```php
use Lisoing\Holidays\Holidays;
use Lisoing\Countries\Cambodia;

// Using country helper with holiday() method
$holidays = Holidays::for(Cambodia::holiday(), year: 2025, locale: 'en')->get();

// Using country helper instance
$holidays = Holidays::for(Cambodia::make(), year: 2025, locale: 'en')->get();

// Or by country code string
$holidays = Holidays::for('KH', year: 2025, locale: 'en')->get();
```

Translations are resolved from `resources/lang/cambodia/{locale}/holidays.php`, mirroring the structure used by Spatie's package and enabling contributors to add locales with a single file.

### Calendar conversions

The package supports three types of calendars:

1. **Solar Calendar** (Gregorian) - Pure sun-based, 365/366 days/year
2. **Lunisolar Calendar** (Khmer, Chinese, etc.) - Moon + Sun with leap months, ~354-384 days/year
3. **Lunar Calendar** (Islamic/Hijri) - Pure moon-based, ~354 days/year

#### Basic Conversions

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar;
use Lisoing\Countries\Cambodia;

$gregorian = CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh');

// Convert to lunisolar calendar
$lunisolarDate = Calendar::for(Cambodia::class)
    ->fromCarbon($gregorian)
    ->toLunisolar();
echo $lunisolarDate->getCalendar();  // km

// Convert to Islamic (lunar) calendar
$islamicDate = Calendar::for('gregorian')
    ->fromCarbon($gregorian)
    ->toIslamic();
echo $islamicDate->getCalendar();  // islamic

// Convert back to Gregorian (solar)
$backToGregorian = $lunisolarDate->toGregorian();
echo $backToGregorian->getCalendar();  // gregorian
```

#### Calendar Type Detection

```php
// Check calendar type
Calendar::isSolar('gregorian');      // true
Calendar::isLunisolar('km');         // true
Calendar::isLunar('islamic');        // true
```

#### Fluent Calendar Switching

```php
// Chain calendar conversions
$result = Calendar::for('gregorian')
    ->fromCarbon($gregorian)
    ->toLunisolar('km')      // Switch to Khmer lunisolar
    ->toGregorian()          // Switch back to Gregorian
    ->toIslamic()            // Switch to Islamic lunar
    ->toString();            // Format as string
```

### Getting Holidays

```php
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\Support\CalendarToolkit;

// Method 1: Using HolidayManager
$manager = app(HolidayManager::class);
$holidays = $manager->forCountry(2025, 'KH', 'en');

foreach ($holidays as $holiday) {
    echo $holiday->name();           // Holiday name
    echo $holiday->date()->format('Y-m-d'); // Date
}

// Method 2: Using CalendarToolkit
$toolkit = CalendarToolkit::make();
$newYear = $toolkit->holiday('khmer_new_year', 2025, 'KH', 'en');
echo $newYear?->name(); // Khmer New Year
```

For more examples, see [USAGE.md](USAGE.md).

## Package Structure

- `src/Calendars/` – Calendar engines implementing `CalendarInterface`
  - `AbstractLunarCalendar.php` – Base class for lunar calendars (Islamic/Hijri)
  - `AbstractLunisolarCalendar.php` – Base class for lunisolar calendars (Khmer, Chinese, etc.)
  - `AbstractSolarCalendar.php` – Base class for solar calendars (Gregorian)
  - `Traits/` – Shared helper methods for each calendar type
- `src/Countries/{Country}/` – Country-specific implementations
  - `Calendars/` – Country calendar implementations
  - `Holidays.php` – Holiday providers
- `src/Support/Cambodia/` – Cambodia-specific formatting and calculation helpers
- `src/Holidays/Countries/{ISO}/` – Country-specific holiday providers (legacy, being migrated to Countries/)
- `resources/lang/{locale}/` – Translation dictionaries for holiday labels
- `tests/Unit` & `tests/Feature` – PHPUnit suites mirroring the src layout

## Roadmap

| Milestone | Focus                                                                 |
|-----------|-----------------------------------------------------------------------|
| v1.0.0    | Gregorian + Khmer lunar calendars, Cambodian holiday provider         |
| v1.1.0    | Additional lunar calendars (Chinese, Thai), expanded translation sets |
| v1.2.0    | Interactive documentation portal and read-only API explorer           |

See `CHANGELOG.md` for released updates.

## Packagist Setup

### First Release

Before the package can be installed via Composer, you need to create the first release:

1. **Create and push the first tag**:
   ```bash
   git tag -a v0.1.0 -m "Initial release"
   git push origin v0.1.0
   ```

2. **Submit to Packagist** (if not already submitted):
   - Visit [packagist.org](https://packagist.org) and log in with your GitHub account
   - Click "Submit" and enter your repository URL: `https://github.com/lisoing/calendar`
   - Packagist will automatically detect the tag and create the package

### Enable Auto-Updates

Once the package is on Packagist, enable automatic updates:

1. **Navigate to your package**: Go to `https://packagist.org/packages/lisoing/calendar`
2. **Set up GitHub Hook**:
   - Click on "Settings" or "Update" button
   - Look for "GitHub Hook" or "Auto-Update" section
   - Click "Update" or "Check Hook" to verify the connection
   - If not set up, click "Set up GitHub Hook" and authorize Packagist to access your repository

Once configured, Packagist will automatically update the package whenever you:
- Push commits to the repository
- Create or update tags (releases)
- Push to the default branch

**Note**: Make sure your repository URL in `composer.json` matches your GitHub repository URL for the hook to work correctly.

## Documentation

- 📖 **[USAGE.md](USAGE.md)** - Complete guide for using Calendar and Holidays
- 📝 **[EXAMPLES.md](EXAMPLES.md)** - Code examples and use cases
- 🧮 **[docs/algorithms.md](docs/algorithms.md)** - Khmer calendar algorithm documentation
- 📚 **[docs/khmer-calendar-history.md](docs/khmer-calendar-history.md)** - Historical background
- 🔧 **[CONTRIBUTING.md](CONTRIBUTING.md)** - How to contribute to the project

### Algorithm Implementation

The Khmer calendar calculations are based on "Pratitin Soryakkatik-Chankatik 1900-1999" by Mr. Roath Kim Soeun. The implementation includes:

- **Core Calculations**: Aharkun, Avoman, Bodithey, Kromathupul
- **Leap Year Determination**: Normal years (354 days), leap-day years (355 days), leap-month years (384 days)
- **Epoch-Based Iteration**: Uses January 1, 1900 as epoch for date conversion
- **Special Cases**: Handles consecutive 24/6, 25/5, and Avoman 137/0 edge cases

See [docs/algorithms.md](docs/algorithms.md) for detailed algorithm documentation.

## Contributing

We welcome new calendars, country providers, translations, and documentation improvements.

- Read [`CONTRIBUTING.md`](CONTRIBUTING.md) for workflow details
- Follow PSR-12 via Laravel Pint (`composer lint`)
- Run the full test suite (`composer test`) before submitting PRs
- Adhere to semantic commit messages (e.g. `feat(country): add khmer national holidays`)

Community impact badges, top contributor highlights, and detailed release notes will celebrate every contribution.

## Security

Please review [`SECURITY.md`](SECURITY.md) for responsible disclosure guidelines.

## License

Released under the [MIT license](LICENSE).

