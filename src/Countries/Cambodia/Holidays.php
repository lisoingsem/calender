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
        $date = match ($entry['resolver'] ?? null) {
            'khmer_new_year' => $this->calculator->getKhmerNewYearDate($year)->toDateString(),
            'lunar_phase' => $this->calculator->toSolar(
                gregorianYear: $year,
                monthSlug: (string) $entry['month_slug'],
                phaseDay: (int) $entry['day'],
                phase: (string) ($entry['phase'] ?? 'waxing')
            )->toDateString(),
            default => throw new \RuntimeException(sprintf(
                'Unknown lunisolar resolver [%s] for holiday [%s].',
                $entry['resolver'] ?? 'null',
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
