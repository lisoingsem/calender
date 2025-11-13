<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Exceptions\CalendarNotFoundException;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class CalendarManager
{
    /**
     * @var Collection<string, CalendarInterface>
     */
    private Collection $calendars;

    public function __construct(
        private readonly string $defaultCalendar,
        private readonly string $fallbackLocale
    ) {
        /** @var Collection<string, CalendarInterface> $calendars */
        $calendars = collect();

        $this->calendars = $calendars;
    }

    public function getDefaultCalendar(): string
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

    public function calendar(string $identifier): CalendarInterface
    {
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
        $identifier = $calendarIdentifier ?? $this->defaultCalendar;

        $calendar = $this->calendar($identifier);

        return $calendar->fromDateTime($dateTime);
    }
}
