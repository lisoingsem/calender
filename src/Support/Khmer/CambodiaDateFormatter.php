<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\Facades\Calendar;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Cambodia-specific date formatting helper.
 * This contains all Cambodia-specific formatting logic.
 */
final class CambodiaDateFormatter
{
    /**
     * Resolve locale from parameter or Laravel's current locale.
     * Uses CalendarManager's locale resolution (configured in service provider).
     */
    private static function resolveLocale(?string $locale): string
    {
        return Calendar::resolveLocale($locale);
    }

    /**
     * Format lunar day like chhankitek (e.g., "១កើត", "១៤រោច").
     */
    public static function formatDay(CalendarDate $date, ?string $locale = null): string
    {
        $locale = self::resolveLocale($locale);
        $phase = $date->getContextValue('phase', $date->getDay() <= 15 ? 'waxing' : 'waning');
        $phaseDay = ($date->getDay() % 15) + 1;

        // Load translations
        $phases = Lang::get("cambodia::lunisolar.phases", [], $locale);

        $phaseLabel = is_array($phases) ? ($phases[$phase] ?? ($phase === 'waxing' ? 'កើត' : 'រោច')) : ($phase === 'waxing' ? 'កើត' : 'រោច');

        // Use Khmer digits only for Khmer locale, regular digits for other locales
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            $khmerDigits = LunisolarConstants::khmerDigits();
            $dayStr = (string) $phaseDay;
            $khmerDay = '';
            foreach (str_split($dayStr) as $digit) {
                $khmerDay .= $khmerDigits[$digit] ?? $digit;
            }
            return $khmerDay . $phaseLabel;
        }

        // For non-Khmer locales, use regular digits
        return (string) $phaseDay . $phaseLabel;
    }

    /**
     * Get day of week name.
     */
    public static function getDayOfWeek(CalendarDate $date, ?string $locale = null): string
    {
        $weekdaySlug = $date->getContextValue('weekday_slug');
        if ($weekdaySlug === null || $weekdaySlug === '') {
            return '';
        }

        $locale = self::resolveLocale($locale);
        $key = "cambodia::lunisolar.weekdays";
        $labels = Lang::get($key, [], $locale);

        // Check if translation was found (not the key itself)
        if (is_array($labels) && $labels !== [] && isset($labels[$weekdaySlug])) {
            return (string) $labels[$weekdaySlug];
        }

        // Fallback: return slug if translation not found
        return (string) $weekdaySlug;
    }

    /**
     * Get lunar month name.
     */
    public static function getLunarMonth(CalendarDate $date, ?string $locale = null): string
    {
        $monthSlug = $date->getContextValue('month_slug');
        if ($monthSlug === null || $monthSlug === '') {
            return '';
        }

        $locale = self::resolveLocale($locale);
        $key = "cambodia::lunisolar.lunar_months";
        $labels = Lang::get($key, [], $locale);

        // Check if translation was found (not the key itself)
        if (is_array($labels) && $labels !== [] && isset($labels[$monthSlug])) {
            return (string) $labels[$monthSlug];
        }

        // Fallback: return slug if translation not found
        return (string) $monthSlug;
    }

    /**
     * Get Buddhist Era year with alternative numbers.
     */
    public static function getLunarYear(CalendarDate $date, ?string $locale = null): string
    {
        $beYear = $date->getContextValue('buddhist_era_year');
        if ($beYear === null) {
            return (string) $date->getYear();
        }

        return self::formatAlternativeNumber((int) $beYear, self::resolveLocale($locale));
    }

    /**
     * Get animal year name.
     */
    public static function getAnimalYear(CalendarDate $date, ?string $locale = null): string
    {
        $animalYearSlug = $date->getContextValue('animal_year_slug');
        if ($animalYearSlug === null || $animalYearSlug === '') {
            return '';
        }

        $locale = self::resolveLocale($locale);
        $key = "cambodia::lunisolar.animal_years";
        $labels = Lang::get($key, [], $locale);

        // Check if translation was found (not the key itself)
        if (is_array($labels) && $labels !== [] && isset($labels[$animalYearSlug])) {
            return (string) $labels[$animalYearSlug];
        }

        // Fallback: return slug if translation not found
        return (string) $animalYearSlug;
    }

    /**
     * Get era year name.
     */
    public static function getEraYear(CalendarDate $date, ?string $locale = null): string
    {
        $eraYearSlug = $date->getContextValue('era_year_slug');
        if ($eraYearSlug === null || $eraYearSlug === '') {
            return '';
        }

        $locale = self::resolveLocale($locale);
        $key = "cambodia::lunisolar.era_years";
        $labels = Lang::get($key, [], $locale);

        // Check if translation was found (not the key itself)
        if (is_array($labels) && $labels !== [] && isset($labels[$eraYearSlug])) {
            return (string) $labels[$eraYearSlug];
        }

        // Fallback: return slug if translation not found
        return (string) $eraYearSlug;
    }

    /**
     * Get moon phase name.
     */
    public static function getPhase(CalendarDate $date, ?string $locale = null): string
    {
        $phase = $date->getContextValue('phase', $date->getDay() <= 15 ? 'waxing' : 'waning');
        $locale = self::resolveLocale($locale);

        $phases = Lang::get("cambodia::lunisolar.phases", [], $locale);

        if (is_array($phases) && isset($phases[$phase])) {
            return $phases[$phase];
        }

        return $phase === 'waxing' ? 'កើត' : 'រោច';
    }

    /**
     * Get full formatted string like chhankitek.
     */
    public static function toString(CalendarDate $date, ?string $locale = null): string
    {
        $locale = self::resolveLocale($locale);
        $dayOfWeek = self::getDayOfWeek($date, $locale);
        $lunarDay = self::formatDay($date, $locale);
        $lunarMonth = self::getLunarMonth($date, $locale);
        $animalYear = self::getAnimalYear($date, $locale);
        $eraYear = self::getEraYear($date, $locale);
        $lunarYear = self::getLunarYear($date, $locale);

        return "ថ្ងៃ{$dayOfWeek} {$lunarDay} ខែ{$lunarMonth} ឆ្នាំ{$animalYear} {$eraYear} ពុទ្ធសករាជ {$lunarYear}";
    }

    /**
     * Format number with alternative digits (Khmer, etc.) based on locale.
     */
    public static function formatAlternativeNumber(int $number, string $locale): string
    {
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            $khmerDigits = LunisolarConstants::khmerDigits();
            $numberStr = (string) $number;
            $result = '';

            foreach (str_split($numberStr) as $digit) {
                $result .= $khmerDigits[$digit] ?? $digit;
            }

            return $result;
        }

        return (string) $number;
    }
}

