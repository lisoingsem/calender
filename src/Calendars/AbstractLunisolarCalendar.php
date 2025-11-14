<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\LunisolarCalendarInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;
use RuntimeException;

/**
 * Abstract base class for lunisolar calendar implementations.
 *
 * This class provides common functionality for lunisolar calendars,
 * making it easier to create new calendar implementations.
 *
 * To create a new lunisolar calendar:
 * 1. Extend this class
 * 2. Implement the abstract methods
 * 3. Provide a calculator that implements LunisolarCalculatorInterface
 */
abstract class AbstractLunisolarCalendar implements LunisolarCalendarInterface
{
    protected string $timezone;

    /**
     * Create a new lunisolar calendar instance.
     *
     * @param  string  $defaultTimezone  Default timezone for this calendar
     */
    public function __construct(string $defaultTimezone)
    {
        $this->timezone = $defaultTimezone;
    }

    /**
     * Get the calculator instance for this calendar.
     * Override this method to provide a custom calculator.
     *
     * @return object Calculator instance that implements toLunar() and toSolar() methods
     */
    abstract protected function getCalculator(): object;

    /**
     * Get the default timezone for this calendar.
     * Override this method to change the default timezone.
     *
     * @return string Timezone identifier (e.g., 'Asia/Phnom_Penh')
     */
    abstract protected function getDefaultTimezone(): string;

    /**
     * Get month slug from month index.
     * Override this method to provide custom month slug resolution.
     *
     * @param  int  $monthIndex  Month index (0-based)
     * @return string Month slug
     */
    abstract protected function getMonthSlug(int $monthIndex): string;

    /**
     * Get month index from month slug.
     * Override this method to provide custom month index resolution.
     *
     * @param  string  $monthSlug  Month slug
     * @return int Month index (0-based)
     */
    abstract protected function getMonthIndex(string $monthSlug): int;

    /**
     * Build context data for CalendarDate.
     * Override this method to add custom context data.
     *
     * @param  CarbonImmutable  $dateTime  The gregorian date
     * @param  object  $lunarData  The lunar data from calculator
     * @return array<string, mixed> Context data array
     */
    abstract protected function buildContext(CarbonImmutable $dateTime, object $lunarData): array;

    /**
     * Extract month slug from lunar data.
     * Override this method if your calculator returns month data differently.
     *
     * @param  object  $lunarData  The lunar data from calculator
     * @return string Month slug
     */
    abstract protected function extractMonthSlug(object $lunarData): string;

    /**
     * Extract day from lunar data.
     * Override this method if your calculator returns day data differently.
     *
     * @param  object  $lunarData  The lunar data from calculator
     * @return int Day number
     */
    abstract protected function extractDay(object $lunarData): int;

    /**
     * Extract phase from lunar data.
     * Override this method if your calculator returns phase data differently.
     *
     * @param  object  $lunarData  The lunar data from calculator
     * @return string Phase ('waxing' or 'waning')
     */
    abstract protected function extractPhase(object $lunarData): string;

    /**
     * Convert lunar date to solar (gregorian) date.
     * Override this method for custom conversion logic.
     *
     * @param  CalendarDate  $date  The lunar date
     * @return CarbonImmutable The gregorian date
     */
    protected function convertToSolar(CalendarDate $date): CarbonImmutable
    {
        $monthSlug = $this->resolveMonthSlug($date);
        $phase = $this->resolvePhase($date);

        $calculator = $this->getCalculator();

        return $calculator->toSolar(
            gregorianYear: $date->getYear(),
            monthSlug: $monthSlug,
            phaseDay: $date->getDay(),
            phase: $phase
        );
    }

    public function configure(array $settings): void
    {
        $timezone = $settings['timezone'] ?? null;

        if (is_string($timezone) && $timezone !== '') {
            $this->timezone = $timezone;
        }
    }

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        // Try to use stored gregorian date if available
        $storedDate = $date->getContextValue('gregorian_date');

        $timezone = $date->getContextValue('timezone', $this->timezone);
        $tz = is_string($timezone) && $timezone !== '' ? $timezone : $this->timezone;

        if (is_string($storedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $storedDate) === 1) {
            $immutable = CarbonImmutable::createFromFormat('Y-m-d', $storedDate, $this->getDefaultTimezone());

            if ($immutable instanceof CarbonImmutable) {
                return $immutable->setTimezone($tz);
            }
        }

        // Convert using calculator
        $solar = $this->convertToSolar($date);

        return $solar->setTimezone($tz);
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        $immutable = CarbonImmutable::instance($dateTime)
            ->setTimezone($this->timezone)
            ->startOfDay();

        $calculator = $this->getCalculator();
        $lunarData = $calculator->toLunar($immutable);

        $monthSlug = $this->extractMonthSlug($lunarData);
        $monthIndex = $this->getMonthIndex($monthSlug);
        $day = $this->extractDay($lunarData);
        $phase = $this->extractPhase($lunarData);

        $context = $this->buildContext($immutable, $lunarData);

        return new CalendarDate(
            year: (int) $immutable->year,
            month: $monthIndex + 1,
            day: $day,
            calendar: $this->identifier(),
            context: array_merge([
                'phase' => $phase,
                'month_slug' => $monthSlug,
                'gregorian_date' => $immutable->toDateString(),
                'timezone' => $immutable->timezoneName,
            ], $context)
        );
    }

    /**
     * Resolve month slug from CalendarDate.
     */
    protected function resolveMonthSlug(CalendarDate $date): string
    {
        $slug = $date->getContextValue('month_slug');

        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        return $this->getMonthSlug($date->getMonth() - 1);
    }

    /**
     * Resolve phase from CalendarDate.
     */
    protected function resolvePhase(CalendarDate $date): string
    {
        $phase = $date->getContextValue('phase');

        if (is_string($phase) && in_array($phase, ['waxing', 'waning'], true)) {
            return $phase;
        }

        return $date->getDay() <= 15 ? 'waxing' : 'waning';
    }
}

