<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Countries\Cambodia;

use Lisoing\Calendar\Holidays\AbstractHolidayProvider;
use Lisoing\Calendar\Support\Cambodia\LunisolarCalculator;

final class Holidays extends AbstractHolidayProvider
{
    private readonly LunisolarCalculator $calculator;

    public function __construct(?LunisolarCalculator $calculator = null)
    {
        $this->calculator = $calculator ?? new LunisolarCalculator();
    }

    public function countryCode(): string
    {
        return 'KH';
    }

    public function name(): string
    {
        return 'Cambodia';
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function definitions(int $year): array
    {
        $config = $this->config();
        $definitions = [];

        foreach ($config['solar'] as $entry) {
            $definitions[] = $this->solarDefinition($entry, $year);
        }

        foreach ($config['lunisolar'] as $entry) {
            $definitions[] = $this->lunisolarDefinition($entry, $year);
        }

        return $definitions;
    }

    protected function countryDirectory(): string
    {
        return 'cambodia';
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function solarDefinition(array $entry, int $year): array
    {
        $month = (int) ($entry['month'] ?? 1);
        $day = (int) ($entry['day'] ?? 1);

        $default = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $date = $this->resolveDateOverride($entry['slug'], $year, $default);

        return [
            'id' => sprintf('%s_%d', $entry['slug'], $year),
            'slug' => $entry['slug'],
            'date' => $date,
            'type' => (string) ($entry['type'] ?? 'public'),
            'title' => $entry['title'] ?? $entry['slug'],
            'description' => $entry['description'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function lunisolarDefinition(array $entry, int $year): array
    {
        $resolver = $entry['resolver'] ?? null;
        
        // Handle multi-day holidays (like Khmer New Year)
        if ($resolver === 'khmer_new_year') {
            return $this->khmerNewYearDefinition($entry, $year);
        }
        
        // Handle single-day lunisolar holidays
        $date = match ($resolver) {
            'lunar_phase' => $this->calculator->toSolar(
                gregorianYear: $year,
                monthSlug: (string) $entry['month_slug'],
                phaseDay: (int) $entry['day'],
                phase: (string) ($entry['phase'] ?? 'waxing')
            )->toDateString(),
            'pchum_ben' => $this->pchumBenDefinition($entry, $year),
            default => throw new \RuntimeException(sprintf(
                'Unknown lunisolar resolver [%s] for holiday [%s].',
                $resolver ?? 'null',
                $entry['slug'] ?? 'unknown'
            )),
        };

        $override = $this->resolveDateOverride($entry['slug'], $year, $date);

        return [
            'id' => sprintf('%s_%d', $entry['slug'], $year),
            'slug' => $entry['slug'],
            'date' => $override,
            'type' => (string) ($entry['type'] ?? 'public'),
            'title' => $entry['title'] ?? $entry['slug'],
            'description' => $entry['description'] ?? null,
        ];
    }

    /**
     * Create definition for Khmer New Year (multi-day holiday).
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, string>
     */
    private function khmerNewYearDefinition(array $entry, int $year): array
    {
        $info = $this->calculator->getKhmerNewYearInfo($year);
        $songkranDate = $info->songkranDate();
        
        // Use Songkran date (first day) as the primary date
        // Store duration and all dates in metadata
        $allDates = $info->allDates();
        $datesList = array_map(fn($d) => $d->toDateString(), $allDates);
        
        return [
            'id' => sprintf('%s_%d', $entry['slug'], $year),
            'slug' => $entry['slug'],
            'date' => $songkranDate->toDateString(),
            'type' => (string) ($entry['type'] ?? 'public'),
            'title' => $entry['title'] ?? $entry['slug'],
            'description' => $entry['description'] ?? null,
            'duration' => (string) $info->duration(),
            'all_dates' => implode(',', $datesList),
            // Cambodia-specific metadata for Khmer New Year
            'songkran_date' => $songkranDate->toDateString(),
            'leungsak_date' => $info->leungsakDate()->toDateString(),
            'vonobot_days' => (string) $info->vonobotDays(),
        ];
    }

    /**
     * Override resolveMetadata to add Cambodia-specific metadata for Khmer New Year.
     *
     * @param  array<string, string>  $definition
     * @return array<string, mixed>
     */
    protected function resolveMetadata(array $definition, string $slug, string $locale): array
    {
        $base = parent::resolveMetadata($definition, $slug, $locale);

        // Add Cambodia-specific metadata for Khmer New Year
        if ($slug === 'khmer_new_year' || str_starts_with($slug, 'khmer_new_year_')) {
            if (isset($definition['songkran_date'])) {
                $base['songkran_date'] = $definition['songkran_date'];
            }
            if (isset($definition['leungsak_date'])) {
                $base['leungsak_date'] = $definition['leungsak_date'];
            }
            if (isset($definition['vonobot_days'])) {
                $base['vonobot_days'] = (int) $definition['vonobot_days'];
            }
            
            // Add formatted day details for each day of the celebration
            $year = (int) (explode('_', $definition['id'])[2] ?? date('Y'));
            $info = $this->calculator->getKhmerNewYearInfo($year);
            $base['day_details'] = $this->formatDayDetails($info, $locale);
            
            // Add angel information
            $angel = $info->angel();
            $base['angel'] = [
                'name_key' => $angel->nameKey(),
                'jewelry_key' => $angel->jewelryKey(),
                'flower_key' => $angel->flowerKey(),
                'food_key' => $angel->foodKey(),
                'right_hand_key' => $angel->rightHandKey(),
                'left_hand_key' => $angel->leftHandKey(),
                'animal_key' => $angel->animalKey(),
            ];
        }

        return $base;
    }

    /**
     * Format detailed information for each day of Khmer New Year.
     *
     * @return array<int, array<string, string>>
     */
    private function formatDayDetails(\Lisoing\Calendar\Support\Cambodia\KhmerNewYearInfo $info, string $locale): array
    {
        $allDates = $info->allDates();
        $dayNames = $info->dayNames();
        $details = [];
        
        foreach ($allDates as $index => $date) {
            $dayName = $dayNames[$index] ?? 'maha_songkran';
            
            // Get full Khmer date string
            $lunar = $this->calculator->toLunar($date);
            $fullKhmerString = $this->formatFullKhmerDate($lunar, $date, $locale);
            
            // Get Gregorian date formatted
            $gregorianDate = $date->format('F j, Y');
            
            // Get ceremony name
            $ceremonyName = $this->getCeremonyName($dayName, $locale);
            
            // Get English translation
            $englishTranslation = $this->getEnglishTranslation($dayName, $info, $date, $locale);
            
            $details[] = [
                'date' => $date->toDateString(),
                'full_khmer_date' => $fullKhmerString,
                'gregorian_date' => $gregorianDate,
                'ceremony_name' => $ceremonyName,
                'english_translation' => $englishTranslation,
                'day_name_key' => $dayName,
            ];
        }
        
        return $details;
    }

    /**
     * Format full Khmer date string.
     */
    private function formatFullKhmerDate(\Lisoing\Calendar\Support\Cambodia\LunarDate $lunar, \Carbon\CarbonImmutable $date, string $locale): string
    {
        $weekdays = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.weekdays", [], $locale);
        $lunarMonths = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.lunar_months", [], $locale);
        $animalYears = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.animal_years", [], $locale);
        $eraYears = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.era_years", [], $locale);
        $solarMonths = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.solar_months", [], $locale);
        $phases = \Illuminate\Support\Facades\Lang::get("cambodia::lunisolar.phases", [], $locale);
        
        // Day of week
        $dayOfWeekKey = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][$lunar->weekdayIndex()] ?? 'sunday';
        $dayOfWeek = is_array($weekdays) ? ($weekdays[$dayOfWeekKey] ?? $dayOfWeekKey) : $dayOfWeekKey;
        
        // Lunar day
        $lunarDay = $lunar->lunarDay();
        $phaseKey = $lunarDay->phaseKey();
        $phase = is_array($phases) ? ($phases[$phaseKey] ?? ($phaseKey === 'waxing' ? 'កើត' : 'រោច')) : ($phaseKey === 'waxing' ? 'កើត' : 'រោច');
        $phaseDay = $lunarDay->day();
        $lunarDayStr = $this->formatNumber($phaseDay, $locale) . $phase;
        
        // Lunar month
        $lunarMonthSlug = $lunar->lunarMonthSlug();
        $lunarMonth = is_array($lunarMonths) ? ($lunarMonths[$lunarMonthSlug] ?? $lunarMonthSlug) : $lunarMonthSlug;
        
        // Animal year
        $animalYearIndex = $lunar->animalYearIndex();
        $animalKeys = ['rat', 'ox', 'tiger', 'rabbit', 'dragon', 'snake', 'horse', 'goat', 'monkey', 'rooster', 'dog', 'pig'];
        $animalKey = $animalKeys[$animalYearIndex] ?? 'rat';
        $animalYear = is_array($animalYears) ? ($animalYears[$animalKey] ?? $animalKey) : $animalKey;
        
        // Era year
        $eraYearIndex = $lunar->eraYearIndex();
        $eraKeys = ['samriddhi_sak', 'eka_sak', 'dvi_sak', 'tri_sak', 'catur_sak', 'pancha_sak', 'sat_sak', 'sapta_sak', 'astha_sak', 'nava_sak'];
        $eraKey = $eraKeys[$eraYearIndex] ?? 'samriddhi_sak';
        $eraYear = is_array($eraYears) ? ($eraYears[$eraKey] ?? $eraKey) : $eraKey;
        
        // Buddhist Era year
        $beYear = $this->formatNumber($lunar->buddhistEraYear(), $locale);
        
        // Gregorian date parts
        $gregorianDay = $this->formatNumber($date->day, $locale);
        $monthKeys = ['mekara', 'kompheak', 'mina', 'mesa', 'ousaphea', 'mithona', 'kakkada', 'seha', 'kakanya', 'tula', 'vicchika', 'thnou'];
        $monthKey = $monthKeys[$date->month - 1] ?? 'mesa';
        $gregorianMonth = is_array($solarMonths) ? ($solarMonths[$monthKey] ?? $monthKey) : $monthKey;
        $gregorianYear = $this->formatNumber($date->year, $locale);
        
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            return "ថ្ងៃ{$dayOfWeek} {$lunarDayStr} ខែ{$lunarMonth} ឆ្នាំ{$animalYear} {$eraYear} ពុទ្ធសករាជ {$beYear} ត្រូវនឹងថ្ងៃទី{$gregorianDay} ខែ{$gregorianMonth} ឆ្នាំ{$gregorianYear}";
        }
        
        return "{$dayOfWeek} {$lunarDayStr} {$lunarMonth} {$animalYear} {$eraYear} Buddhist Era {$beYear} = {$gregorianDay} {$gregorianMonth} {$gregorianYear}";
    }

    /**
     * Get ceremony name for the day.
     */
    private function getCeremonyName(string $dayNameKey, string $locale): string
    {
        $ceremonies = \Illuminate\Support\Facades\Lang::get("cambodia::ceremonies", [], $locale);
        
        $ceremonyName = is_array($ceremonies) ? ($ceremonies[$dayNameKey] ?? $dayNameKey) : $dayNameKey;
        
        // Add "ពិធី​បុណ្យ​ចូល​ឆ្នាំ​ថ្មី ប្រពៃណី​ជាតិ - " prefix for Khmer
        if ($locale === 'km' || str_starts_with($locale, 'km_')) {
            return "ពិធី​បុណ្យ​ចូល​ឆ្នាំ​ថ្មី ប្រពៃណី​ជាតិ - {$ceremonyName}";
        }
        
        return "Khmer New Year - {$ceremonyName}";
    }

    /**
     * Get English translation with time for Songkran day.
     */
    private function getEnglishTranslation(string $dayNameKey, \Lisoing\Calendar\Support\Cambodia\KhmerNewYearInfo $info, \Carbon\CarbonImmutable $date, string $locale): string
    {
        $ceremonies = \Illuminate\Support\Facades\Lang::get("cambodia::ceremonies", [], 'en');
        $ceremonyName = is_array($ceremonies) ? ($ceremonies[$dayNameKey] ?? $dayNameKey) : $dayNameKey;
        
        // For Songkran day, add the time
        if ($dayNameKey === 'maha_songkran') {
            $time = $info->angelDescentTime('en');
            return "(Khmer New Year - {$ceremonyName} at {$time})";
        }
        
        return "(Khmer New Year - {$ceremonyName})";
    }

    /**
     * Format number with Khmer digits if locale is Khmer.
     */
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

    /**
     * Calculate Pchum Ben date (15th day of 10th lunar month - Assuj).
     *
     * Pchum Ben is a 15-day festival ending on the 15th day (full moon) 
     * of the 10th lunar month (Assuj/Bhadrapada).
     *
     * @param  array<string, mixed>  $entry
     * @return string Date string (YYYY-MM-DD)
     */
    private function pchumBenDefinition(array $entry, int $year): string
    {
        // Pchum Ben is the 15th day (full moon) of the 10th lunar month (Assuj)
        // This is the final day of the 15-day festival period
        return $this->calculator->toSolar(
            gregorianYear: $year,
            monthSlug: 'assuj',
            phaseDay: 15,
            phase: 'waxing'
        )->toDateString();
    }

    /**
     * @return array{
     *     solar: array<int, array<string, mixed>>,
     *     lunisolar: array<int, array<string, mixed>>
     * }
     */
    private function config(): array
    {
        static $config;

        if ($config === null) {
            $path = dirname(__DIR__, 3).'/resources/holidays/cambodia.php';

            /** @var array<string, mixed> $loaded */
            $loaded = file_exists($path) ? require $path : [];

            $config = [
                'solar' => is_array($loaded['solar'] ?? null) ? $loaded['solar'] : [],
                'lunisolar' => is_array($loaded['lunisolar'] ?? null) ? $loaded['lunisolar'] : [],
            ];
        }

        return $config;
    }
}
