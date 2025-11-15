<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

use Carbon\CarbonImmutable;

/**
 * Comprehensive information about Khmer New Year for a given year.
 *
 * Contains Songkran date/time, duration, associated angel, and cultural context.
 */
final class KhmerNewYearInfo
{
    /**
     * @param  CarbonImmutable  $songkranDate  Songkran date (first day of New Year)
     * @param  array{0: int, 1: int}  $songkranTime  Songkran time [hour, minute]
     * @param  int  $vonobotDays  Number of Vonobot days (1 or 2)
     * @param  CarbonImmutable  $leungsakDate  Leungsak date (last day of New Year)
     * @param  int  $duration  Total duration in days (3 or 4)
     * @param  int  $dayOfWeek  Day of week for Songkran (0=Sunday, ..., 6=Saturday)
     * @param  NewYearAngel  $angel  Associated New Year angel
     * @param  array{0: int, 1: int}  $leungsakLunar  Leungsak lunar date [day, month]
     */
    public function __construct(
        private readonly CarbonImmutable $songkranDate,
        private readonly array $songkranTime,
        private readonly int $vonobotDays,
        private readonly CarbonImmutable $leungsakDate,
        private readonly int $duration,
        private readonly int $dayOfWeek,
        private readonly NewYearAngel $angel,
        private readonly array $leungsakLunar
    ) {}

    public function songkranDate(): CarbonImmutable
    {
        return $this->songkranDate;
    }

    /**
     * @return array{0: int, 1: int} [hour, minute]
     */
    public function songkranTime(): array
    {
        return $this->songkranTime;
    }

    public function vonobotDays(): int
    {
        return $this->vonobotDays;
    }

    public function leungsakDate(): CarbonImmutable
    {
        return $this->leungsakDate;
    }

    public function duration(): int
    {
        return $this->duration;
    }

    public function dayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function angel(): NewYearAngel
    {
        return $this->angel;
    }

    /**
     * @return array{0: int, 1: int} [day, month]
     */
    public function leungsakLunar(): array
    {
        return $this->leungsakLunar;
    }

    /**
     * Get all New Year dates (Songkran, Vonabot days, Leungsak).
     *
     * @return array<int, CarbonImmutable>
     */
    public function allDates(): array
    {
        $dates = [];

        // Add all days from Songkran to Leungsak
        for ($i = 0; $i < $this->duration; $i++) {
            $dates[] = $this->songkranDate->addDays($i)->startOfDay();
        }

        return $dates;
    }

    /**
     * Get day names for each day of the celebration.
     *
     * @return array<int, string>
     */
    public function dayNames(): array
    {
        $names = ['maha_songkran'];

        // Add Vonabot days (middle days)
        for ($i = 1; $i < $this->duration - 1; $i++) {
            $names[] = 'vara_vanabat';
        }

        // Add Leungsak (last day)
        $names[] = 'vara_loeng_sak';

        return $names;
    }

    /**
     * Get the angel descent time (ម៉ោងទេវតាចុះ).
     * This is the exact time when the New Year angel descends to earth.
     *
     * @return string Formatted time (e.g., "ម៉ោង ០៤:០០ AM")
     */
    public function angelDescentTime(?string $locale = null): string
    {
        $locale = $this->resolveLocale($locale);
        $hour = $this->songkranTime[0];
        $minute = $this->songkranTime[1];
        
        $formattedTime = sprintf('%02d:%02d', $hour, $minute);
        $period = $hour >= 12 ? 'PM' : 'AM';
        
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            $khmerDigits = \Lisoing\Calendar\Support\Cambodia\LunisolarConstants::khmerDigits();
            $timeStr = '';
            foreach (str_split($formattedTime) as $char) {
                $timeStr .= $khmerDigits[$char] ?? $char;
            }
            return "ម៉ោង {$timeStr} {$period}";
        }
        
        return "ម៉ោង {$formattedTime} {$period}";
    }

    /**
     * Format full Khmer New Year information in Khmer format.
     * Example: "ថ្ងៃពុធ ៣កើត ខែពិសាខ ឆ្នាំឆ្លូវ ត្រីស័ក ពុទ្ធសករាជ ២៥៦៤ ត្រូវនឹងថ្ងៃទី១៤ ខែមេសា ឆ្នាំ២០២១"
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Formatted string
     */
    public function toFullKhmerString(?string $locale = null): string
    {
        $locale = $this->resolveLocale($locale);
        
        // Get lunar date for Songkran
        $calculator = new LunisolarCalculator();
        $lunar = $calculator->toLunar($this->songkranDate);
        
        // Format components
        $dayOfWeek = $this->formatDayOfWeek($locale);
        $lunarDay = $this->formatLunarDay($lunar, $locale);
        $lunarMonth = $this->formatLunarMonth($lunar->lunarMonthSlug(), $locale);
        $animalYear = $this->formatAnimalYear($lunar->animalYearIndex(), $locale);
        $eraYear = $this->formatEraYear($lunar->eraYearIndex(), $locale);
        $beYear = $this->formatBEYear($lunar->buddhistEraYear(), $locale);
        
        // Gregorian date
        $gregorianDay = $this->formatNumber($this->songkranDate->day, $locale);
        $gregorianMonth = $this->formatGregorianMonth($this->songkranDate->month, $locale);
        $gregorianYear = $this->formatNumber($this->songkranDate->year, $locale);
        
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            return "ថ្ងៃ{$dayOfWeek} {$lunarDay} ខែ{$lunarMonth} ឆ្នាំ{$animalYear} {$eraYear} ពុទ្ធសករាជ {$beYear} ត្រូវនឹងថ្ងៃទី{$gregorianDay} ខែ{$gregorianMonth} ឆ្នាំ{$gregorianYear}";
        }
        
        return "{$dayOfWeek} {$lunarDay} {$lunarMonth} {$animalYear} {$eraYear} Buddhist Era {$beYear} = {$gregorianDay} {$gregorianMonth} {$gregorianYear}";
    }

    /**
     * Get full lunar calendar information (ប្រតិទិនចន្ទគតិ).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return array<string, mixed> Lunar calendar information
     */
    public function lunarCalendarInfo(?string $locale = null): array
    {
        $locale = $this->resolveLocale($locale);
        $calculator = new LunisolarCalculator();
        $lunar = $calculator->toLunar($this->songkranDate);
        
        return [
            'day_of_week' => $this->formatDayOfWeek($locale),
            'lunar_day' => $this->formatLunarDay($lunar, $locale),
            'lunar_month' => $this->formatLunarMonth($lunar->lunarMonthSlug(), $locale),
            'animal_year' => $this->formatAnimalYear($lunar->animalYearIndex(), $locale),
            'era_year' => $this->formatEraYear($lunar->eraYearIndex(), $locale),
            'buddhist_era_year' => $this->formatBEYear($lunar->buddhistEraYear(), $locale),
            'gregorian_date' => [
                'day' => $this->songkranDate->day,
                'month' => $this->songkranDate->month,
                'year' => $this->songkranDate->year,
                'formatted' => $this->songkranDate->format('Y-m-d'),
            ],
        ];
    }

    private function resolveLocale(?string $locale): string
    {
        if ($locale !== null && $locale !== '') {
            return $locale;
        }
        
        if (class_exists(\Illuminate\Support\Facades\App::class)) {
            return \Illuminate\Support\Facades\App::getLocale() ?: 'en';
        }
        
        return 'en';
    }

    private function formatLunarDay(LunarDate $lunar, string $locale): string
    {
        $lunarDay = $lunar->lunarDay();
        $phaseKey = $lunarDay->phaseKey();
        $phase = $phaseKey === 'waxing' ? 'waxing' : 'waning';
        $phaseDay = $lunarDay->day();
        
        $phases = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.phases", [], $locale);
        $phaseLabel = is_array($phases) ? ($phases[$phase] ?? ($phase === 'waxing' ? 'កើត' : 'រោច')) : ($phase === 'waxing' ? 'កើត' : 'រោច');
        
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            $khmerDigits = \Lisoing\Calendar\Support\Cambodia\LunisolarConstants::khmerDigits();
            $dayStr = (string) $phaseDay;
            $khmerDay = '';
            foreach (str_split($dayStr) as $digit) {
                $khmerDay .= $khmerDigits[$digit] ?? $digit;
            }
            return $khmerDay . $phaseLabel;
        }
        
        return "{$phaseDay}{$phaseLabel}";
    }

    private function formatDayOfWeek(string $locale): string
    {
        $weekdays = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.weekdays", [], $locale);
        $dayOfWeekKey = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][$this->dayOfWeek] ?? 'sunday';
        
        return is_array($weekdays) ? ($weekdays[$dayOfWeekKey] ?? $dayOfWeekKey) : $dayOfWeekKey;
    }

    private function formatLunarMonth(string $monthSlug, string $locale): string
    {
        $months = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.lunar_months", [], $locale);
        
        return is_array($months) ? ($months[$monthSlug] ?? $monthSlug) : $monthSlug;
    }

    private function formatAnimalYear(int $index, string $locale): string
    {
        $animals = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.animal_years", [], $locale);
        $animalKeys = ['rat', 'ox', 'tiger', 'rabbit', 'dragon', 'snake', 'horse', 'goat', 'monkey', 'rooster', 'dog', 'pig'];
        $key = $animalKeys[$index] ?? 'rat';
        
        return is_array($animals) ? ($animals[$key] ?? $key) : $key;
    }

    private function formatEraYear(int $index, string $locale): string
    {
        $eras = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.era_years", [], $locale);
        $eraKeys = ['samriddhi_sak', 'eka_sak', 'dvi_sak', 'tri_sak', 'catur_sak', 'pancha_sak', 'sat_sak', 'sapta_sak', 'astha_sak', 'nava_sak'];
        $key = $eraKeys[$index] ?? 'samriddhi_sak';
        
        return is_array($eras) ? ($eras[$key] ?? $key) : $key;
    }

    private function formatBEYear(int $beYear, string $locale): string
    {
        return $this->formatNumber($beYear, $locale);
    }

    private function formatGregorianMonth(int $month, string $locale): string
    {
        $months = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.solar_months", [], $locale);
        $monthKeys = ['mekara', 'kompheak', 'mina', 'mesa', 'ousaphea', 'mithona', 'kakkada', 'seha', 'kakanya', 'tula', 'vicchika', 'thnou'];
        $key = $monthKeys[$month - 1] ?? 'mesa';
        
        return is_array($months) ? ($months[$key] ?? $key) : $key;
    }

    private function formatNumber(int $number, string $locale): string
    {
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            $khmerDigits = \Lisoing\Calendar\Support\Cambodia\LunisolarConstants::khmerDigits();
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

