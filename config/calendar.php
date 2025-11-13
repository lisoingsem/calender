<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Calendar Identifier
    |--------------------------------------------------------------------------
    */
    'default_calendar' => 'gregorian',

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    */
    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Calendar Registry
    |--------------------------------------------------------------------------
    |
    | Map calendar identifiers to their concrete classes. Consumers can
    | override or extend this list by publishing the configuration file and
    | binding their own implementations.
    |
    */
    'calendars' => [
        'gregorian' => \Lisoing\Calendar\Calendars\GregorianCalendar::class,
        'khmer_chhankitek' => \Lisoing\Calendar\Calendars\Khmer\KhmerChhankitekCalendar::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration passed to configurable calendars. Each entry is keyed by
    | the same identifier declared in the calendar registry.
    |
    */
    'calendar_settings' => [
        'khmer_chhankitek' => [
            'reference_dates' => [
                2024 => [
                    '01-01' => '2024-02-10',
                    '05-15' => '2024-06-21',
                    '13-01' => '2024-04-13',
                ],
                2025 => [
                    '01-01' => '2025-01-29',
                    '05-15' => '2025-06-10',
                    '13-01' => '2025-04-14',
                ],
            ],
            'leap_years' => [
                2024 => [
                    'type' => 'adhikameas',
                    'explanation' => 'Double Ashad months to align lunar and solar cycle.',
                ],
            ],
            'metadata' => [
                'default_timezone' => 'Asia/Phnom_Penh',
            ],
            'cache' => [
                'enabled' => true,
                'ttl' => 60 * 60 * 24 * 365,
                'prefix' => 'calendar:khmer',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Provider Map
    |--------------------------------------------------------------------------
    */
    'countries' => [
        'KH' => \Lisoing\Calendar\Holidays\Countries\Cambodia::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Holiday Provider Settings
    |--------------------------------------------------------------------------
    |
    | Optional configuration passed to holiday providers that implement the
    | configurable interface. Keys should match the ISO country code used in
    | the provider registry above.
    |
    */
    'holiday_settings' => [
        'KH' => [
            'observances' => [
                'international_new_year' => [],
                'victory_over_genocide_regime' => [],
                'international_womens_day' => [],
                'king_fathers_memorial' => [],
                'khmer_new_year' => [
                    'metadata' => [
                        'notes' => 'Celebrated over three days based on lunar cycle.',
                    ],
                ],
                'visak_bochea' => [],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable parts of the package. When holidays are disabled the
    | HolidayManager binding is skipped and convenience helpers will return
    | empty collections.
    |
    */
    'features' => [
        'holidays' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    */
    'supported_locales' => ['en', 'km'],

    /*
    |--------------------------------------------------------------------------
    | Formatter Mapping
    |--------------------------------------------------------------------------
    |
    | Specify formatter classes for rendering calendar dates in a human
    | readable format. Consumers may override or swap formatters.
    |
    */
    'formatters' => [
        'khmer_chhankitek' => \Lisoing\Calendar\Formatting\LunarFormatter::class,
    ],
];
