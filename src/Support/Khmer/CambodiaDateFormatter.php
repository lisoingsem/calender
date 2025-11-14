<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Cambodia-specific date formatting helper.
 * This contains all Cambodia-specific formatting logic.
 */
final class CambodiaDateFormatter
{
    /**
     * Format lunar day like chhankitek (e.g., "១កើត", "១៤រោច").
     */
    public static function formatDay(CalendarDate $date, ?string $locale = null): string
    {
        $locale = $locale ?? 'km';
        $phase = $date->getContextValue('phase', $date->getDay() <= 15 ? 'waxing' : 'waning');
        $phaseDay = ($date->getDay() % 15) + 1;

        // Load translations
        $phases = Lang::get("cambodia::lunisolar.phases", [], $locale);
        $khmerDigits = LunisolarConstants::khmerDigits();

        $phaseLabel = is_array($phases) ? ($phases[$phase] ?? ($phase === 'waxing' ? 'កើត' : 'រោច')) : ($phase === 'waxing' ? 'កើត' : 'រោច');

        // Convert day number to Khmer digits
        $dayStr = (string) $phaseDay;
        $khmerDay = '';
        foreach (str_split($dayStr) as $digit) {
            $khmerDay .= $khmerDigits[$digit] ?? $digit;
        }

        return $khmerDay . $phaseLabel;
    }

    /**
     * Get day of week name.
     */
    public static function getDayOfWeek(CalendarDate $date, ?string $locale = null): string
    {
        $weekdaySlug = $date->getContextValue('weekday_slug');
        if ($weekdaySlug === null) {
            return '';
        }

        $locale = $locale ?? 'km';
        $labels = Lang::get("cambodia::lunisolar.weekdays", [], $locale);

        if (is_array($labels) && isset($labels[$weekdaySlug])) {
            return $labels[$weekdaySlug];
        }

        return $weekdaySlug;
    }

    /**
     * Get lunar month name.
     */
    public static function getLunarMonth(CalendarDate $date, ?string $locale = null): string
    {
        $monthSlug = $date->getContextValue('month_slug');
        if ($monthSlug === null) {
            return '';
        }

        $locale = $locale ?? 'km';
        $labels = Lang::get("cambodia::lunisolar.lunar_months", [], $locale);

        if (is_array($labels) && isset($labels[$monthSlug])) {
            return $labels[$monthSlug];
        }

        return $monthSlug;
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

        return self::formatAlternativeNumber((int) $beYear, $locale ?? 'km');
    }

    /**
     * Get animal year name.
     */
    public static function getAnimalYear(CalendarDate $date, ?string $locale = null): string
    {
        $animalYearSlug = $date->getContextValue('animal_year_slug');
        if ($animalYearSlug === null) {
            return '';
        }

        $locale = $locale ?? 'km';
        $labels = Lang::get("cambodia::lunisolar.animal_years", [], $locale);

        if (is_array($labels) && isset($labels[$animalYearSlug])) {
            return $labels[$animalYearSlug];
        }

        return $animalYearSlug;
    }

    /**
     * Get era year name.
     */
    public static function getEraYear(CalendarDate $date, ?string $locale = null): string
    {
        $eraYearSlug = $date->getContextValue('era_year_slug');
        if ($eraYearSlug === null) {
            return '';
        }

        $locale = $locale ?? 'km';
        $labels = Lang::get("cambodia::lunisolar.era_years", [], $locale);

        if (is_array($labels) && isset($labels[$eraYearSlug])) {
            return $labels[$eraYearSlug];
        }

        return $eraYearSlug;
    }

    /**
     * Get moon phase name.
     */
    public static function getPhase(CalendarDate $date, ?string $locale = null): string
    {
        $phase = $date->getContextValue('phase', $date->getDay() <= 15 ? 'waxing' : 'waning');
        $locale = $locale ?? 'km';

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
        $locale = $locale ?? 'km';
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

