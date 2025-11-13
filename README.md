# calendar

Universal lunar and solar calendar package for Laravel and PHP, maintained by a global community. It brings worldwide holiday data, multilingual translations, and a welcoming contribution workflow inspired by the best open-source practices.

## Highlights

- 🔭 Dual calendar support with an extensible manager (Gregorian + Khmer lunar included)
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
use Lisoing\Calendar\Facades\Calendar;
use Lisoing\Calendar\ValueObjects\CalendarDate;

$khmerNewYear = new CalendarDate(2025, 13, 1, 'khmer_lunar');

$gregorian = Calendar::convert($khmerNewYear, 'gregorian');

echo $gregorian->getYear();  // 2025
echo $gregorian->getMonth(); // 4
echo $gregorian->getDay();   // 14
```

Retrieve holidays for Cambodia (translations fallback to English automatically):

```php
use Lisoing\Calendar\Holidays\HolidayManager;

$holidays = app(HolidayManager::class)->forCountry(2025, 'KH', 'km');

foreach ($holidays as $holiday) {
    echo "{$holiday->name()} ({$holiday->date()->toDateString()})";
}
```

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

