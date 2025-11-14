<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Exceptions\CalendarNotFoundException;
use Lisoing\Calendar\Support\CalendarContext;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class CalendarManager
{
    /**
     * @var Collection<string, CalendarInterface>
     */
    private Collection $calendars;

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    public function __construct(
        private readonly ?string $defaultCalendar = null,
        private readonly string $fallbackLocale = 'en'
    ) {
        /** @var Collection<string, CalendarInterface> $calendars */
        $calendars = collect();

        $this->calendars = $calendars;
    }

    public function getDefaultCalendar(): ?string
    {
        return $this->defaultCalendar;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function register(CalendarInterface $calendar): void
    {
        $this->calendars->put($calendar->identifier(), $calendar);
    }

    public function registerAlias(string $alias, string $calendarIdentifier): void
    {
        $this->aliases[strtoupper($alias)] = $calendarIdentifier;
    }

    public function calendar(string $identifier): CalendarInterface
    {
        $identifier = $this->aliases[strtoupper($identifier)] ?? $identifier;

        $calendar = $this->calendars->get($identifier);

        if ($calendar === null) {
            $available = $this->calendars
                ->keys()
                ->map(static fn (mixed $key): string => (string) $key)
                ->values()
                ->all();

            throw CalendarNotFoundException::make($identifier, $available);
        }

        return $calendar;
    }

    public function convert(CalendarDate $date, string $targetIdentifier): CalendarDate
    {
        $target = $this->calendar($targetIdentifier);

        $source = $this->calendar($date->getCalendar());

        $carbon = $source->toDateTime($date);

        return $target->fromDateTime($carbon);
    }

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        return $this->calendar($date->getCalendar())->toDateTime($date);
    }

    public function fromDateTime(CarbonInterface $dateTime, ?string $calendarIdentifier = null): CalendarDate
    {
        if ($calendarIdentifier === null && $this->defaultCalendar === null) {
            throw new \RuntimeException(
                'No calendar identifier provided and no default calendar is set. ' .
                'Please specify a calendar: Calendar::for("km")->fromCarbon($date) or set a default calendar in config.'
            );
        }

        $identifier = $calendarIdentifier ?? $this->defaultCalendar;

        $calendar = $this->calendar($identifier);

        return $calendar->fromDateTime($dateTime);
    }

    public function toLunar(CarbonInterface $dateTime, ?string $calendarIdentifier = null): CalendarDate
    {
        return $this->fromDateTime($dateTime, $calendarIdentifier);
    }

    public function toSolar(CalendarDate $date, string $targetIdentifier = 'gregorian'): CarbonInterface
    {
        $converted = $this->convert($date, $targetIdentifier);

        return $this->toDateTime($converted);
    }

    /**
     * @param  string|\Lisoing\Calendar\Contracts\CalendarInterface  $calendar
     */
    public function for(string|CalendarInterface $calendar): CalendarContext
    {
        return $this->using($calendar);
    }

    /**
     * @param  string|\Lisoing\Calendar\Contracts\CalendarInterface  $calendar
     */
    public function using(string|CalendarInterface $calendar): CalendarContext
    {
        $identifier = $calendar instanceof CalendarInterface
            ? $calendar->identifier()
            : (string) $calendar;

        return new CalendarContext($this, $identifier);
    }

    /**
     * Parse a date string into a calendar date (Carbon-like API).
     *
     * @param  string  $date  Date string (e.g., '2025-04-14', '2025-04-14 10:30')
     * @param  string|null  $calendar  Calendar identifier (defaults to default calendar)
     * @param  string|null  $timezone  Timezone (defaults to calendar's timezone)
     * @return CalendarDate
     */
    public function parse(string $date, ?string $calendar = null, ?string $timezone = null): CalendarDate
    {
        $carbon = \Carbon\CarbonImmutable::parse($date, $timezone);

        return $this->fromDateTime($carbon, $calendar);
    }

    /**
     * Get current date in calendar (Carbon-like API).
     *
     * @param  string|null  $calendar  Calendar identifier (defaults to default calendar)
     * @param  string|null  $timezone  Timezone (defaults to calendar's timezone)
     * @return CalendarDate
     */
    public function now(?string $calendar = null, ?string $timezone = null): CalendarDate
    {
        $carbon = \Carbon\CarbonImmutable::now($timezone);

        return $this->fromDateTime($carbon, $calendar);
    }

    /**
     * Create a calendar date from year, month, day (Carbon-like API).
     *
     * @param  int  $year  Year
     * @param  int  $month  Month (1-12 or 1-14 for lunisolar)
     * @param  int  $day  Day (1-31)
     * @param  string|null  $calendar  Calendar identifier
     * @param  string|null  $timezone  Timezone
     * @return CalendarDate
     */
    public function create(int $year, int $month, int $day, ?string $calendar = null, ?string $timezone = null): CalendarDate
    {
        $carbon = \Carbon\CarbonImmutable::create($year, $month, $day, 0, 0, 0, $timezone);

        return $this->fromDateTime($carbon, $calendar);
    }
}
