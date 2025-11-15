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
        }

        return $base;
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
