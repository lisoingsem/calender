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

