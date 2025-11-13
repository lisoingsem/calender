<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class KhmerLunarCalendar extends AbstractCalendar
{
    private const IDENTIFIER = 'khmer_lunar';

    /**
     * Limited reference data that maps Khmer lunar festival dates to Gregorian equivalents.
     *
     * @var array<int, array<string, string>>
     */
    private const REFERENCE = [
        2024 => [
            '01-01' => '2024-02-10',
            '05-15' => '2024-06-21',
            '13-01' => '2024-04-13',
        ],
        2025 => [
            '01-01' => '2025-01-29',
            '05-15' => '2025-06-10',
            '13-01' => '2025-04-14',
        ],
    ];

    public function identifier(): string
    {
        return self::IDENTIFIER;
    }

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        $key = sprintf('%02d-%02d', $date->getMonth(), $date->getDay());

        $reference = self::REFERENCE[$date->getYear()] ?? null;

        if ($reference !== null && array_key_exists($key, $reference)) {
            return CarbonImmutable::parse($reference[$key]);
        }

        $hint = $date->getContextValue('gregorian_hint');

        if ($hint instanceof CarbonInterface) {
            return CarbonImmutable::instance($hint);
        }

        if (is_string($hint)) {
            return CarbonImmutable::parse($hint);
        }

        return parent::toDateTime($date);
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        foreach (self::REFERENCE as $year => $mappings) {
            foreach ($mappings as $key => $gregorianDate) {
                if ($dateTime->isSameDay(CarbonImmutable::parse($gregorianDate))) {
                    [$month, $day] = array_map(static fn (string $value): int => (int) $value, explode('-', $key));

                    return new CalendarDate($year, $month, $day, self::IDENTIFIER);
                }
            }
        }

        return parent::fromDateTime($dateTime)->withContext('approximate', true);
    }
}

