<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Illuminate\Support\Collection;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Exceptions\CalendarNotFoundException;
use Lisoing\Calendar\ValueObjects\CalendarDate;
use Carbon\CarbonInterface;

final class CalendarManager
{
    /**
     * @var Collection<int, CalendarInterface>
     */
    private Collection $calendars;

    public function __construct(
        private readonly string $defaultCalendar,
        private readonly string $fallbackLocale
    ) {
        $this->calendars = collect();
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
            throw CalendarNotFoundException::make($identifier, $this->calendars->keys()->all());
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

