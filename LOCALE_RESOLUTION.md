# Locale Resolution in Calendar Package

## How Locale is Resolved

The calendar package automatically resolves locale from Laravel in the following order:

1. **Explicit locale parameter** (if provided)
2. **Laravel's App Locale** (`App::getLocale()`)
3. **Laravel's Fallback Locale** (`Config::get('app.fallback_locale')`)
4. **Package's default locale** (`Config::get('calendar.fallback_locale')` or `'en'`)

## Where Locale is Used

### 1. CalendarManager
- `resolveLocale(?string $locale): string` - Resolves locale for calendar operations
- Uses `App::getLocale()` and `Config::get('app.fallback_locale')` as fallbacks

### 2. HolidayManager
- `resolveLocale(?string $locale): string` - Resolves locale for holiday translations
- Uses `App::getLocale()` and `Config::get('app.fallback_locale')` as fallbacks
- Automatically uses Laravel's locale when `$locale` is `null`

### 3. Holiday Translations
- **Title/Name**: Translated using `HolidayTranslator::translate()`
- **Description**: Translated using `resolveDescription()` method
- Translation files: `resources/lang/{country}/{locale}/holidays.php`

## Translation Files Structure

```
resources/lang/
  └── cambodia/
      ├── en/
      │   └── holidays.php
      └── km/
          └── holidays.php
```

## Example Usage

```php
// Automatically uses Laravel's locale
$holidays = Calendar::for(Cambodia::class)->holidays()->get(2024);

// Explicitly set locale
$holidays = Calendar::for(Cambodia::class)->holidays('km')->get(2024);

// In a controller, it will use App::getLocale()
public function index() {
    // Uses Laravel's current locale automatically
    $holidays = Calendar::for(Cambodia::class)->holidays()->get(2024);
    return response()->json($holidays);
}
```

## Holiday Output Structure

When a holiday is returned, it includes:

- **id**: Unique identifier (e.g., `khmer_new_year_2024`)
- **name**: Translated holiday name (e.g., "Khmer New Year" or "បុណ្យចូលឆ្នាំខ្មែរ")
- **title**: Same as name (for compatibility)
- **description**: Translated description (e.g., "The most important holiday in Cambodia...")
- **date**: Primary date (YYYY-MM-DD format)
- **country**: Country code (e.g., "KH")
- **locale**: The locale used for translations (e.g., "en" or "km")
- **metadata**: Additional information including:
  - `type`: Holiday type (e.g., "public")
  - `duration`: Number of days (for multi-day holidays)
  - `all_dates`: Array of all dates for multi-day holidays
  - Country-specific metadata (e.g., `songkran_date`, `leungsak_date`, `vonobot_days` for Khmer New Year)

## Translation Keys

Holiday translations use keys from the holiday definition:

- **Title key**: From `title` field in holiday definition (e.g., `khmer_new_year`)
- **Description key**: From `description` field in holiday definition (e.g., `khmer_new_year_description`)

These keys are looked up in `resources/lang/{country}/{locale}/holidays.php`.

