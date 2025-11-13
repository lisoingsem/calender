# calendar

Universal lunar and solar calendar package for Laravel and PHP, maintained by a global community. It brings worldwide holiday data, multilingual translations, and a welcoming contribution workflow inspired by the best open-source practices.

## Highlights

- 🔭 Dual calendar support with an extensible manager (Gregorian + Cambodia lunar included)
- 🏮 Holiday providers organised per country with translation-ready labels
- 🌍 Localization files ready for contributors (`resources/lang/{locale}/holidays.php`)
- ⚙️ Laravel-first experience with auto-discovery, service provider, and facade
- ✅ PSR-12 compliant codebase with Laravel Pint, PHPUnit, and PHPStan

## Installation

```bash
composer require lisoing/calendar
```

Publish the configuration file if you need to customise defaults:

```bash
php artisan vendor:publish --tag=calendar-config
```

## Quick Start

```php
use Carbon\CarbonImmutable;
use Lisoing\Calendar\Calendars\CambodiaCalendar;
use Lisoing\Calendar\Facades\Calendar;
use Lisoing\Calendar\ValueObjects\CalendarDate;

$khmerNewYear = new CalendarDate(2025, 13, 1, 'khmer');

$gregorianDate = Calendar::convert($khmerNewYear, 'gregorian');
echo $gregorianDate->getYear();  // 2025
echo $gregorianDate->getMonth(); // 4
echo $gregorianDate->getDay();   // 14

$calendar = app(CambodiaCalendar::class);
$calendarDate = $calendar->fromDateTime(CarbonImmutable::parse('2025-04-14', 'Asia/Phnom_Penh'));

$holiday = Toolkit::holiday('khmer_new_year', 2025, 'KH', 'en');
echo $holiday?->name(); // Khmer New Year

$dates = Toolkit::holidayDates(2025, 'KH');
echo $dates['khmer_new_year_2025']->toDateString(); // 2025-04-14
```

Retrieve holidays for Cambodia (translations fallback to English automatically):

```php
foreach (Toolkit::holidays(2025, 'KH', 'km') as $holiday) {
    echo "{$holiday->name()} ({$holiday->date()->toDateString()})";
}
```

Holiday translations are resolved from `lang/cambodia/{locale}/holidays.php`, mirroring the structure used by Spatie's holidays project and enabling contributors to drop in additional locales with a single file.

## Package Structure

- `src/Calendars/` – Calendar engines implementing `CalendarInterface`
- `src/Holidays/Countries/{ISO}/` – Country-specific holiday providers
- `resources/lang/{locale}/` – Translation dictionaries for holiday labels
- `tests/Unit` & `tests/Feature` – PHPUnit suites mirroring the src layout
- `config/calendar.php` – Publishable configuration options

## Roadmap

| Milestone | Focus                                                                 |
|-----------|-----------------------------------------------------------------------|
| v1.0.0    | Gregorian + Khmer lunar calendars, Cambodian holiday provider         |
| v1.1.0    | Additional lunar calendars (Chinese, Thai), expanded translation sets |
| v1.2.0    | Interactive documentation portal and read-only API explorer           |

See `CHANGELOG.md` for released updates.

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

