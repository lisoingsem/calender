<?php

declare(strict_types=1);

namespace Lisoing\Calendar\ValueObjects;

use Carbon\CarbonInterface;
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

    private function assertValid(): void
    {
        if ($this->month < 1 || $this->month > 13) {
            throw new InvalidArgumentException('Month must be between 1 and 13.');
        }

        if ($this->day < 1 || $this->day > 31) {
            throw new InvalidArgumentException('Day must be between 1 and 31.');
        }

        if ($this->calendar === '') {
            throw new InvalidArgumentException('Calendar identifier cannot be empty.');
        }
    }
}
