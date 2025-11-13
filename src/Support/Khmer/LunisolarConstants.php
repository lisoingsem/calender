<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

use Illuminate\Support\Facades\Config;
use RuntimeException;

final class LunisolarConstants
{
    public const TIMEZONE = 'Asia/Phnom_Penh';

    /** @var array<int, string> */
    private const LUNAR_MONTH_SLUGS = [
        'mekasira',
        'pous',
        'makha',
        'phalgun',
        'cetra',
        'visak',
        'jesh',
        'asadha',
        'srapoan',
        'bhadrapada',
        'assuj',
        'kattik',
        'adhika_asadha_first',
        'adhika_asadha_second',
    ];

    /** @var array<int, string> */
    private const SOLAR_MONTH_SLUGS = [
        'mekara',
        'kompheak',
        'mina',
        'mesa',
        'ousaphea',
        'mithona',
        'kakkada',
        'seha',
        'kakanya',
        'tula',
        'vicchika',
        'thnou',
    ];

    /** @var array<int, string> */
    private const ANIMAL_YEAR_SLUGS = [
        'rat',
        'ox',
        'tiger',
        'rabbit',
        'dragon',
        'snake',
        'horse',
        'goat',
        'monkey',
        'rooster',
        'dog',
        'pig',
    ];

    /** @var array<int, string> */
    private const ERA_YEAR_SLUGS = [
        'samriddhi_sak',
        'eka_sak',
        'dvi_sak',
        'tri_sak',
        'catur_sak',
        'pancha_sak',
        'sat_sak',
        'sapta_sak',
        'astha_sak',
        'nava_sak',
    ];

    /** @var array<int, string> */
    private const DAY_OF_WEEK_SLUGS = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    /** @var array<int, string> */
    private const MOON_PHASE_SLUGS = [
        'waxing',
        'waning',
    ];

    /** @var array<string, int>|null */
    private static ?array $lunarMonths = null;

    /** @var array<int, string>|null */
    private static ?array $lunarMonthIndexes = null;

    /** @var array<string, int>|null */
    private static ?array $solarMonths = null;

    /** @var array<string, int>|null */
    private static ?array $animalYears = null;

    /** @var array<string, int>|null */
    private static ?array $eraYears = null;

    /** @var array<string, int>|null */
    private static ?array $dayOfWeeks = null;

    /** @var array<string, int>|null */
    private static ?array $moonPhases = null;

    /** @var array<string, string>|null */
    private static ?array $khmerDigits = null;

    /** @var array<string, array<string, array<string, string>>> */
    private static array $labelCache = [];

    /**
     * @return array<string, int>
     */
    public static function lunarMonths(): array
    {
        if (self::$lunarMonths === null) {
            self::$lunarMonths = [];
            self::$lunarMonthIndexes = [];

            foreach (self::LUNAR_MONTH_SLUGS as $index => $slug) {
                self::$lunarMonths[$slug] = $index;
                self::$lunarMonthIndexes[$index] = $slug;
            }
        }

        return self::$lunarMonths;
    }

    public static function lunarMonthSlug(int $index): string
    {
        self::lunarMonths();

        if (! isset(self::$lunarMonthIndexes[$index])) {
            throw new RuntimeException(sprintf('Unknown lunar month index [%d].', $index));
        }

        return self::$lunarMonthIndexes[$index];
    }

    public static function lunarMonthLabels(?string $locale = null): array
    {
        return self::resolveLabels(self::LUNAR_MONTH_SLUGS, 'lunar_months', $locale);
    }

    /**
     * @return array<string, int>
     */
    public static function solarMonths(): array
    {
        if (self::$solarMonths === null) {
            self::$solarMonths = [];

            foreach (self::SOLAR_MONTH_SLUGS as $index => $slug) {
                self::$solarMonths[$slug] = $index;
            }
        }

        return self::$solarMonths;
    }

    public static function solarMonthLabels(?string $locale = null): array
    {
        return self::resolveLabels(self::SOLAR_MONTH_SLUGS, 'solar_months', $locale);
    }

    /**
     * @return array<string, int>
     */
    public static function animalYears(): array
    {
        if (self::$animalYears === null) {
            self::$animalYears = [];

            foreach (self::ANIMAL_YEAR_SLUGS as $index => $slug) {
                self::$animalYears[$slug] = $index;
            }
        }

        return self::$animalYears;
    }

    public static function animalYearLabels(?string $locale = null): array
    {
        return self::resolveLabels(self::ANIMAL_YEAR_SLUGS, 'animal_years', $locale);
    }

    /**
     * @return array<string, int>
     */
    public static function eraYears(): array
    {
        if (self::$eraYears === null) {
            self::$eraYears = [];

            foreach (self::ERA_YEAR_SLUGS as $index => $slug) {
                self::$eraYears[$slug] = $index;
            }
        }

        return self::$eraYears;
    }

    public static function eraYearLabels(?string $locale = null): array
    {
        return self::resolveLabels(self::ERA_YEAR_SLUGS, 'era_years', $locale);
    }

    /**
     * @return array<string, int>
     */
    public static function dayOfWeeks(): array
    {
        if (self::$dayOfWeeks === null) {
            self::$dayOfWeeks = [];

            foreach (self::DAY_OF_WEEK_SLUGS as $index => $slug) {
                self::$dayOfWeeks[$slug] = $index;
            }
        }

        return self::$dayOfWeeks;
    }

    public static function dayOfWeekLabels(?string $locale = null): array
    {
        return self::resolveLabels(self::DAY_OF_WEEK_SLUGS, 'weekdays', $locale);
    }

    /**
     * @return array<string, int>
     */
    public static function moonPhases(): array
    {
        if (self::$moonPhases === null) {
            self::$moonPhases = [];

            foreach (self::MOON_PHASE_SLUGS as $index => $slug) {
                self::$moonPhases[$slug] = $index;
            }
        }

        return self::$moonPhases;
    }

    public static function moonPhaseLabels(?string $locale = null): array
    {
        return self::resolveLabels(self::MOON_PHASE_SLUGS, 'phases', $locale);
    }

    /**
     * @return array<string, string>
     */
    public static function khmerDigits(): array
    {
        if (self::$khmerDigits === null) {
            self::$khmerDigits = [
                '0' => '០',
                '1' => '១',
                '2' => '២',
                '3' => '៣',
                '4' => '៤',
                '5' => '៥',
                '6' => '៦',
                '7' => '៧',
                '8' => '៨',
                '9' => '៩',
            ];
        }

        return self::$khmerDigits;
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<string, string>
     */
    private static function resolveLabels(array $slugs, string $group, ?string $locale = null): array
    {
        $resolved = [];

        foreach ($slugs as $slug) {
            $resolved[$slug] = $slug;
        }

        foreach (self::candidateLocales($locale) as $candidate) {
            $labels = self::loadLocaleLabels($candidate);

            if (! isset($labels[$group]) || ! is_array($labels[$group])) {
                continue;
            }

            /** @var array<string, string> $groupLabels */
            $groupLabels = $labels[$group];

            foreach ($slugs as $slug) {
                if (isset($groupLabels[$slug]) && is_string($groupLabels[$slug]) && $groupLabels[$slug] !== '') {
                    $resolved[$slug] = $groupLabels[$slug];
                }
            }

            break;
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    private static function candidateLocales(?string $preferred): array
    {
        $locales = [];

        if (is_string($preferred) && $preferred !== '') {
            $locales[] = strtolower($preferred);
        }

        $configured = Config::get('calendar.fallback_locale');

        if (is_string($configured) && $configured !== '') {
            $locales[] = strtolower($configured);
        }

        $locales[] = 'en';
        $locales[] = 'km';

        return array_values(array_unique($locales));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function loadLocaleLabels(string $locale): array
    {
        if (! isset(self::$labelCache[$locale])) {
            $path = self::languageDirectory().'/'.$locale.'/lunisolar.php';

            if (file_exists($path)) {
                /** @var mixed $data */
                $data = require $path;
                self::$labelCache[$locale] = is_array($data) ? $data : [];
            } else {
                self::$labelCache[$locale] = [];
            }
        }

        return self::$labelCache[$locale];
    }

    private static function languageDirectory(): string
    {
        return dirname(__DIR__, 2).'/resources/lang/cambodia';
    }
}

