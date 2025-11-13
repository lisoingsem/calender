<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Calendar Identifier
    |--------------------------------------------------------------------------
    |
    | This option controls the default calendar implementation that the
    | package uses when no explicit calendar is specified. The identifier
    | must match a registered calendar class within the CalendarManager.
    |
    */

    'default_calendar' => 'gregorian',

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | Translation strings for holiday names are resolved via Laravel's
    | localization features. When a translation is missing for the
    | requested locale, the fallback locale will be used instead.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Country Provider Map
    |--------------------------------------------------------------------------
    |
    | Providers listed here will be auto-registered by the package's service
    | provider. Each key should be an ISO 3166 alpha-2 country code mapped
    | to the fully-qualified class name for the holiday provider.
    |
    */

    'countries' => [
        'KH' => \Lisoing\Calendar\Holidays\Countries\KH\KhmerNationalHolidays::class,
    ],
];
