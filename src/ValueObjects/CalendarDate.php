<?php

declare(strict_types=1);

namespace Lisoing\Calendar\ValueObjects;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;

final class CalendarDate
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
        private readonly string $calendar,
        private readonly array $context = []
    ) {
        $this->assertValid();
    }

    public static function fromDateTime(CarbonInterface $dateTime, string $calendar): self
    {
        return new self(
            year: (int) $dateTime->year,
            month: (int) $dateTime->month,
            day: (int) $dateTime->day,
            calendar: $calendar
        );
    }

    public function withContext(string $key, mixed $value): self
    {
        $context = $this->context;
        $context[$key] = $value;

        return new self(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            calendar: $this->calendar,
            context: $context
        );
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getDay(): int
    {
        return $this->day;
    }

    public function getCalendar(): string
    {
        return $this->calendar;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Convert CalendarDate to array for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'calendar' => $this->calendar,
            'context' => $this->context,
        ];
    }

    /**
     * Format lunar day (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Formatted day string
     */
    public function formatDay(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::formatDay($this, $locale);
        }

        // Default: return day number
        return (string) $this->day;
    }

    /**
     * Get day of week name (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Day of week name
     */
    public function getDayOfWeek(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::getDayOfWeek($this, $locale);
        }

        // Default: return weekday slug or empty
        return (string) $this->getContextValue('weekday_slug', '');
    }

    /**
     * Get formatted lunar day (like chhankitek).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Formatted lunar day (១កើត, ១៤រោច, etc.)
     */
    public function getLunarDay(?string $locale = null): string
    {
        return $this->formatDay($locale);
    }

    /**
     * Get lunar month name (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Lunar month name
     */
    public function getLunarMonth(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::getLunarMonth($this, $locale);
        }

        // Default: return month slug or empty
        return (string) $this->getContextValue('month_slug', '');
    }

    /**
     * Get lunar year (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Lunar year
     */
    public function getLunarYear(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::getLunarYear($this, $locale);
        }

        // Default: return year or Buddhist Era year from context
        $beYear = $this->getContextValue('buddhist_era_year');
        return $beYear !== null ? (string) $beYear : (string) $this->year;
    }

    /**
     * Get animal year name (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Animal year
     */
    public function getAnimalYear(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::getAnimalYear($this, $locale);
        }

        // Default: return animal year slug or empty
        return (string) $this->getContextValue('animal_year_slug', '');
    }

    /**
     * Get era year name (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Era year
     */
    public function getEraYear(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::getEraYear($this, $locale);
        }

        // Default: return era year slug or empty
        return (string) $this->getContextValue('era_year_slug', '');
    }

    /**
     * Get moon phase name (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @return string Moon phase
     */
    public function getPhase(?string $locale = null): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::getPhase($this, $locale);
        }

        // Default: return phase slug
        $phase = $this->getContextValue('phase', $this->day <= 15 ? 'waxing' : 'waning');
        return (string) $phase;
    }

    /**
     * Format date using Carbon-style format string (reusable for all calendars).
     *
     * @param  string  $format  Format string (e.g., 'dddd D MMMM YYYY', 'OD OM OY')
     * @param  string|null  $locale  Locale for formatting (defaults to calendar's default locale)
     * @return string Formatted date string
     */
    public function format(string $format, ?string $locale = null): string
    {
        // Default locale based on calendar
        $defaultLocale = $this->getDefaultLocale();
        $locale = $locale ?? $defaultLocale;

        $formatter = new \Lisoing\Calendar\Support\CalendarDateFormatter($this, $locale);

        return $formatter->format($format);
    }

    /**
     * Get full formatted string (reusable, calendar-specific formatters handle details).
     *
     * @param  string|null  $locale  Locale for formatting
     * @param  bool  $includeStructureWords  Whether to include structure words (for Khmer: ថ្ងៃ, ខែ, ឆ្នាំ)
     * @return string Full formatted date string
     */
    public function toString(?string $locale = null, bool $includeStructureWords = true): string
    {
        // Use calendar-specific formatter if available
        if ($this->calendar === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::toString($this, $locale, $includeStructureWords);
        }

        // For gregorian calendar, use a full readable format
        if ($this->calendar === 'gregorian') {
            // Convert to Carbon to get day of week and month name
            $carbon = \Lisoing\Calendar\Facades\Calendar::toDateTime($this);
            
            // Use Carbon's locale-aware formatting
            // If locale is provided, use it; otherwise use app's current locale
            $carbonLocale = $locale ?? \Illuminate\Support\Facades\App::getLocale();
            if ($carbonLocale !== null && $carbonLocale !== '') {
                $carbon = $carbon->locale($carbonLocale);
            }
            
            return $carbon->isoFormat('dddd, MMMM D, YYYY');
        }

        // Default: use Carbon-style format
        return $this->format('YYYY-MM-DD', $locale);
    }

    /**
     * Get default locale for this calendar.
     */
    private function getDefaultLocale(): string
    {
        // Calendar-specific default locales
        return match ($this->calendar) {
            'km' => 'km',
            default => 'en',
        };
    }

    /**
     * Magic method for string conversion.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Add days to the date (Carbon-like API).
     * Returns a new CalendarDate instance.
     *
     * @param  int  $days  Number of days to add
     * @return CalendarDate New CalendarDate instance
     */
    public function addDays(int $days): CalendarDate
    {
        $carbon = \Lisoing\Calendar\Facades\Calendar::toDateTime($this);
        $newCarbon = $carbon->addDays($days);

        return \Lisoing\Calendar\Facades\Calendar::fromDateTime($newCarbon, $this->calendar);
    }

    /**
     * Subtract days from the date (Carbon-like API).
     * Returns a new CalendarDate instance.
     *
     * @param  int  $days  Number of days to subtract
     * @return CalendarDate New CalendarDate instance
     */
    public function subDays(int $days): CalendarDate
    {
        return $this->addDays(-$days);
    }

    /**
     * Add months to the date (Carbon-like API).
     * Returns a new CalendarDate instance.
     *
     * @param  int  $months  Number of months to add
     * @return CalendarDate New CalendarDate instance
     */
    public function addMonths(int $months): CalendarDate
    {
        $carbon = \Lisoing\Calendar\Facades\Calendar::toDateTime($this);
        $newCarbon = $carbon->addMonths($months);

        return \Lisoing\Calendar\Facades\Calendar::fromDateTime($newCarbon, $this->calendar);
    }

    /**
     * Subtract months from the date (Carbon-like API).
     * Returns a new CalendarDate instance.
     *
     * @param  int  $months  Number of months to subtract
     * @return CalendarDate New CalendarDate instance
     */
    public function subMonths(int $months): CalendarDate
    {
        return $this->addMonths(-$months);
    }

    /**
     * Add years to the date (Carbon-like API).
     * Returns a new CalendarDate instance.
     *
     * @param  int  $years  Number of years to add
     * @return CalendarDate New CalendarDate instance
     */
    public function addYears(int $years): CalendarDate
    {
        $carbon = \Lisoing\Calendar\Facades\Calendar::toDateTime($this);
        $newCarbon = $carbon->addYears($years);

        return \Lisoing\Calendar\Facades\Calendar::fromDateTime($newCarbon, $this->calendar);
    }

    /**
     * Subtract years from the date (Carbon-like API).
     * Returns a new CalendarDate instance.
     *
     * @param  int  $years  Number of years to subtract
     * @return CalendarDate New CalendarDate instance
     */
    public function subYears(int $years): CalendarDate
    {
        return $this->addYears(-$years);
    }

    /**
     * Convert to Carbon instance (Carbon-like API).
     *
     * @return CarbonInterface Carbon instance
     */
    public function toCarbon(): CarbonInterface
    {
        return \Lisoing\Calendar\Facades\Calendar::toDateTime($this);
    }

    /**
     * Check if date is today (Carbon-like API).
     *
     * @return bool
     */
    public function isToday(): bool
    {
        $carbon = $this->toCarbon();
        $today = \Carbon\CarbonImmutable::now($carbon->timezone);

        return $carbon->isSameDay($today);
    }

    /**
     * Check if date is in the past (Carbon-like API).
     *
     * @return bool
     */
    public function isPast(): bool
    {
        $carbon = $this->toCarbon();
        $now = \Carbon\CarbonImmutable::now($carbon->timezone);

        return $carbon->isPast();
    }

    /**
     * Check if date is in the future (Carbon-like API).
     *
     * @return bool
     */
    public function isFuture(): bool
    {
        $carbon = $this->toCarbon();
        $now = \Carbon\CarbonImmutable::now($carbon->timezone);

        return $carbon->isFuture();
    }

    private function assertValid(): void
    {
        if ($this->month < 1 || $this->month > 14) {
            throw new InvalidArgumentException('Month must be between 1 and 14.');
        }

        if ($this->day < 1 || $this->day > 31) {
            throw new InvalidArgumentException('Day must be between 1 and 31.');
        }

        if ($this->calendar === '') {
            throw new InvalidArgumentException('Calendar identifier cannot be empty.');
        }
    }
}
