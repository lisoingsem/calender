<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Lisoing\Calendar\CalendarManager;
use Lisoing\Calendar\Formatting\FormatterManager;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\ValueObjects\CalendarDate;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Calendar\ValueObjects\HolidayCollection;

final class CalendarToolkit
{
    public function __construct(
        private readonly CalendarManager $calendarManager,
        private readonly ?FormatterManager $formatterManager = null,
        private readonly ?HolidayManager $holidayManager = null
    ) {
    }

    public function toSolar(CalendarDate|array $lunar, string $targetCalendar = 'gregorian'): CalendarDate
    {
        $calendarDate = $this->normalizeCalendarDate($lunar);

        return $this->calendarManager->convert($calendarDate, $targetCalendar);
    }

    public function toLunar(CarbonInterface $dateTime, string $calendarIdentifier = 'khmer'): CalendarDate
    {
        return $this->calendarManager->fromDateTime($dateTime, $calendarIdentifier);
    }

    public function toDateTime(CalendarDate|array $date): CarbonInterface
    {
        $calendarDate = $this->normalizeCalendarDate($date);

        return $this->calendarManager->toDateTime($calendarDate);
    }

    public function format(CalendarDate|array $date, ?string $locale = null): string
    {
        if ($this->formatterManager === null) {
            throw new \RuntimeException('Formatter feature is disabled.');
        }

        $calendarDate = $this->normalizeCalendarDate($date);

        return $this->formatterManager->format($calendarDate, $locale);
    }

    public function holidays(int $year, string $countryCode, ?string $locale = null): HolidayCollection
    {
        if ($this->holidayManager === null) {
            return new HolidayCollection;
        }

        return $this->holidayManager->forCountry($year, $countryCode, $locale);
    }

    public function holiday(
        string $slug,
        int $year,
        string $countryCode,
        ?string $locale = null
    ): ?Holiday {
        foreach ($this->holidays($year, $countryCode, $locale) as $holiday) {
            if ($holiday->identifier() === $slug || str_starts_with($holiday->identifier(), "{$slug}_")) {
                return $holiday;
            }
        }

        return null;
    }

    public function holidayDates(int $year, string $countryCode, ?string $locale = null): Collection
    {
        $holidays = $this->holidays($year, $countryCode, $locale);

        return collect($holidays->all())->mapWithKeys(
            static fn (Holiday $holiday): array => [$holiday->identifier() => $holiday->date()]
        );
    }

    public function isHolidaysEnabled(): bool
    {
        return $this->holidayManager !== null;
    }

    public function calendars(): Collection
    {
        $reflection = new \ReflectionClass($this->calendarManager);
        $property = $reflection->getProperty('calendars');
        $property->setAccessible(true);

        /** @var Collection<string, mixed> $registered */
        $registered = $property->getValue($this->calendarManager);

        return $registered->keys();
    }

    /**
     * @param  CalendarDate|array{year:int,month:int,day:int,calendar?:string}  $date
     */
    private function normalizeCalendarDate(CalendarDate|array $date): CalendarDate
    {
        if ($date instanceof CalendarDate) {
            return $date;
        }

        return new CalendarDate(
            year: $date['year'],
            month: $date['month'],
            day: $date['day'],
            calendar: $date['calendar'] ?? $this->calendarManager->getDefaultCalendar()
        );
    }
}

