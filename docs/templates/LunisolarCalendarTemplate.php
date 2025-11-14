<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

/**
 * TEMPLATE: Lunisolar Calendar Implementation
 *
 * Copy this file and rename it to YourCountryCalendar.php
 * Then implement all the abstract methods below.
 *
 * This template makes it easy to create new lunisolar calendars
 * by handling all the common boilerplate code.
 */

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Support\YourCountry\YourCalculator;
use Lisoing\Calendar\Support\YourCountry\YourConstants;
use RuntimeException;

final class YourCountryCalendar extends AbstractLunisolarCalendar
{
    private ?YourCalculator $calculator = null;

    public function __construct(?YourCalculator $calculator = null)
    {
        // Set your country's default timezone
        parent::__construct(YourConstants::TIMEZONE);
        $this->calculator = $calculator;
    }

    /**
     * Return your calendar identifier (e.g., 'np', 'cn', 'th').
     */
    public function identifier(): string
    {
        return 'your_code';
    }

    /**
     * Return your calculator instance.
     * The calculator should have toLunar() and toSolar() methods.
     */
    protected function getCalculator(): object
    {
        if ($this->calculator === null) {
            $this->calculator = new YourCalculator();
        }

        return $this->calculator;
    }

    /**
     * Return your country's default timezone.
     */
    protected function getDefaultTimezone(): string
    {
        return YourConstants::TIMEZONE;
    }

    /**
     * Convert month index (0-based) to month slug.
     */
    protected function getMonthSlug(int $monthIndex): string
    {
        return YourConstants::lunarMonthSlug($monthIndex);
    }

    /**
     * Convert month slug to month index (0-based).
     */
    protected function getMonthIndex(string $monthSlug): int
    {
        $months = YourConstants::lunarMonths();

        if (! array_key_exists($monthSlug, $months)) {
            throw new RuntimeException(sprintf('Unknown lunar month slug [%s].', $monthSlug));
        }

        return $months[$monthSlug];
    }

    /**
     * Build additional context data for CalendarDate.
     * Add any country-specific data here (e.g., animal year, era year).
     */
    protected function buildContext(CarbonImmutable $dateTime, object $lunarData): array
    {
        // Example: Add custom context data
        return [
            // 'weekday_slug' => YourConstants::dayOfWeekSlug($lunarData->weekdayIndex()),
            // 'animal_year_slug' => YourConstants::animalYearSlug($lunarData->animalYearIndex()),
            // Add more as needed
        ];
    }

    /**
     * Extract month slug from your calculator's return value.
     */
    protected function extractMonthSlug(object $lunarData): string
    {
        // Example implementations:
        
        // If your calculator returns an object with a method:
        // return $lunarData->lunarMonthSlug();
        
        // If your calculator returns an object with a property:
        // return $lunarData->monthSlug;
        
        // If your calculator returns an array:
        // return $lunarData['month_slug'];
        
        throw new RuntimeException('Implement extractMonthSlug() method.');
    }

    /**
     * Extract day number from your calculator's return value.
     */
    protected function extractDay(object $lunarData): int
    {
        // Example implementations:
        
        // If your calculator returns an object with a method:
        // return $lunarData->lunarDay()->day();
        
        // If your calculator returns an object with a property:
        // return $lunarData->day;
        
        // If your calculator returns an array:
        // return $lunarData['day'];
        
        throw new RuntimeException('Implement extractDay() method.');
    }

    /**
     * Extract phase ('waxing' or 'waning') from your calculator's return value.
     */
    protected function extractPhase(object $lunarData): string
    {
        // Example implementations:
        
        // If your calculator returns an object with a method:
        // return $lunarData->lunarDay()->phaseKey();
        
        // If your calculator returns an object with a property:
        // return $lunarData->phase;
        
        // If your calculator returns an array:
        // return $lunarData['phase'];
        
        throw new RuntimeException('Implement extractPhase() method.');
    }
}

