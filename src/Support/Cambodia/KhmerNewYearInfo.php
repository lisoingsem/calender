<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

use Carbon\CarbonImmutable;

/**
 * Comprehensive information about Khmer New Year for a given year.
 *
 * Contains Songkran date/time, duration, associated angel, and cultural context.
 */
final class KhmerNewYearInfo
{
    /**
     * @param  CarbonImmutable  $songkranDate  Songkran date (first day of New Year)
     * @param  array{0: int, 1: int}  $songkranTime  Songkran time [hour, minute]
     * @param  int  $vonobotDays  Number of Vonobot days (1 or 2)
     * @param  CarbonImmutable  $leungsakDate  Leungsak date (last day of New Year)
     * @param  int  $duration  Total duration in days (3 or 4)
     * @param  int  $dayOfWeek  Day of week for Songkran (0=Sunday, ..., 6=Saturday)
     * @param  NewYearAngel  $angel  Associated New Year angel
     * @param  array{0: int, 1: int}  $leungsakLunar  Leungsak lunar date [day, month]
     */
    public function __construct(
        private readonly CarbonImmutable $songkranDate,
        private readonly array $songkranTime,
        private readonly int $vonobotDays,
        private readonly CarbonImmutable $leungsakDate,
        private readonly int $duration,
        private readonly int $dayOfWeek,
        private readonly NewYearAngel $angel,
        private readonly array $leungsakLunar
    ) {}

    public function songkranDate(): CarbonImmutable
    {
        return $this->songkranDate;
    }

    /**
     * @return array{0: int, 1: int} [hour, minute]
     */
    public function songkranTime(): array
    {
        return $this->songkranTime;
    }

    public function vonobotDays(): int
    {
        return $this->vonobotDays;
    }

    public function leungsakDate(): CarbonImmutable
    {
        return $this->leungsakDate;
    }

    public function duration(): int
    {
        return $this->duration;
    }

    public function dayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function angel(): NewYearAngel
    {
        return $this->angel;
    }

    /**
     * @return array{0: int, 1: int} [day, month]
     */
    public function leungsakLunar(): array
    {
        return $this->leungsakLunar;
    }

    /**
     * Get all New Year dates (Songkran, Vonabot days, Leungsak).
     *
     * @return array<int, CarbonImmutable>
     */
    public function allDates(): array
    {
        $dates = [];

        // Add all days from Songkran to Leungsak
        for ($i = 0; $i < $this->duration; $i++) {
            $dates[] = $this->songkranDate->addDays($i)->startOfDay();
        }

        return $dates;
    }

    /**
     * Get day names for each day of the celebration.
     *
     * @return array<int, string>
     */
    public function dayNames(): array
    {
        $names = ['maha_songkran'];

        // Add Vonabot days (middle days)
        for ($i = 1; $i < $this->duration - 1; $i++) {
            $names[] = 'vara_vanabat';
        }

        // Add Leungsak (last day)
        $names[] = 'vara_loeng_sak';

        return $names;
    }
}

