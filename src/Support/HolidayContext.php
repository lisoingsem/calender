<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Calendar\ValueObjects\HolidayCollection;
use Lisoing\Calendar\ValueObjects\HolidayResult;

/**
 * Fluent API for holiday operations.
 * 
 * Provides helper methods to access holidays by camelCase name:
 * 
 * For Cambodia:
 * - khmerNewYear() -> 'khmer_new_year'
 * - visakBochea() -> 'visak_bochea'
 * - pchumBen() -> 'pchum_ben'
 * - meakBochea() -> 'meak_bochea'
 * - chineseNewYear() -> 'chinese_new_year'
 * - internationalNewYear() -> 'international_new_year'
 * - victoryOverGenocideRegime() -> 'victory_over_genocide_regime'
 * - internationalWomensDay() -> 'international_womens_day'
 * - kingFathersMemorial() -> 'king_fathers_memorial'
 * 
 * Usage:
 * ```php
 * $holiday = Calendar::for(Cambodia::class)->holidays()->khmerNewYear();
 * $holiday = Calendar::for(Cambodia::class)->holidays()->khmerNewYear(2024);
 * $holiday = Calendar::for(Cambodia::class)->holidays()->year(2024)->khmerNewYear();
 * ```
 */
final class HolidayContext
{
    private ?int $year = null;
    private ?string $locale = null;

    public function __construct(
        private readonly HolidayManager $holidayManager,
        private readonly string $countryCode
    ) {}

    /**
     * Set the year for holiday queries.
     */
    public function year(int $year): self
    {
        $context = new self($this->holidayManager, $this->countryCode);
        $context->year = $year;
        $context->locale = $this->locale;

        return $context;
    }

    /**
     * Set the locale for holiday names.
     */
    public function locale(?string $locale): self
    {
        $context = new self($this->holidayManager, $this->countryCode);
        $context->year = $this->year;
        $context->locale = $locale;

        return $context;
    }

    /**
     * Get holidays.
     *
     * @param  string|null  $date  Optional date string (YYYY-MM-DD). If provided, returns HolidayResult for that date.
     * @return HolidayCollection|HolidayResult
     */
    public function get(?string $date = null): HolidayCollection|HolidayResult
    {
        // If date is provided, get holidays for that specific date
        if ($date !== null) {
            return $this->getForDate($date);
        }

        // Otherwise, get all holidays for the year
        $year = $this->year ?? CarbonImmutable::now()->year;
        $locale = $this->locale;

        return $this->holidayManager->forCountry($year, $this->countryCode, $locale);
    }

    /**
     * Check if a date is a holiday.
     * Supports multi-day holidays by checking if date falls within holiday ranges.
     *
     * @param  string  $date  Date string (YYYY-MM-DD)
     */
    public function isHoliday(string $date): bool
    {
        $result = $this->getForDate($date);

        return ! $result->isEmpty();
    }

    /**
     * Magic method to access holidays by camelCase name.
     * 
     * Examples:
     * - khmerNewYear() -> looks for 'khmer_new_year' holiday
     * - visakBochea() -> looks for 'visak_bochea' holiday
     * - pchumBen() -> looks for 'pchum_ben' holiday
     *
     * @param  string  $method  Method name in camelCase
     * @param  array<int, mixed>  $arguments  Method arguments (optional year can be passed)
     * @return HolidayResult
     */
    public function __call(string $method, array $arguments): HolidayResult
    {
        // Convert camelCase to snake_case
        $slug = $this->camelToSnake($method);

        // Get year from argument or use default
        $year = $arguments[0] ?? ($this->year ?? \Carbon\CarbonImmutable::now()->year);

        // Get all holidays for the year
        $locale = $this->locale;
        $allHolidays = $this->holidayManager->forCountry($year, $this->countryCode, $locale);

        // Find holiday by slug
        $matchingHolidays = [];
        foreach ($allHolidays as $holiday) {
            $holidaySlug = $this->extractSlugFromIdentifier($holiday->identifier());
            if ($holidaySlug === $slug) {
                $matchingHolidays[] = $holiday;
            }
        }

        return new HolidayResult($matchingHolidays);
    }

    /**
     * Convert camelCase to snake_case.
     */
    private function camelToSnake(string $camel): string
    {
        // Insert underscore before uppercase letters (except first)
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $camel);
        
        // Convert to lowercase
        return strtolower($snake);
    }

    /**
     * Extract slug from holiday identifier.
     * Identifier format: 'slug_year' (e.g., 'khmer_new_year_2024')
     */
    private function extractSlugFromIdentifier(string $identifier): string
    {
        // Remove year suffix (last underscore and year)
        if (preg_match('/^(.+)_\d{4}$/', $identifier, $matches)) {
            return $matches[1];
        }

        return $identifier;
    }

    /**
     * Get holidays for a specific date.
     * Checks both exact date matches and multi-day holiday ranges.
     */
    private function getForDate(string $date): HolidayResult
    {
        $parsedDate = CarbonImmutable::parse($date);
        $year = $parsedDate->year;
        $dateString = $parsedDate->format('Y-m-d');

        $locale = $this->locale;
        $allHolidays = $this->holidayManager->forCountry($year, $this->countryCode, $locale);

        $matchingHolidays = [];

        foreach ($allHolidays as $holiday) {
            $holidayDate = $holiday->date()->format('Y-m-d');

            // Check exact date match
            if ($holidayDate === $dateString) {
                $matchingHolidays[] = $holiday;
                continue;
            }

            // Check if date falls within multi-day holiday range
            $metadata = $holiday->metadata();
            if (isset($metadata['all_dates']) && is_array($metadata['all_dates'])) {
                if (in_array($dateString, $metadata['all_dates'], true)) {
                    $matchingHolidays[] = $holiday;
                }
            }
        }

        return new HolidayResult($matchingHolidays);
    }
}

