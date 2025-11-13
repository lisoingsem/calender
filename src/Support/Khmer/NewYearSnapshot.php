<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

final class NewYearSnapshot
{
    /**
     * @param  SolarNewYearDay[]  $sotins
     */
    public function __construct(
        private readonly int $aharkun,
        private readonly int $kromathupul,
        private readonly int $avoman,
        private readonly int $bodithey,
        private readonly bool $hasSolarLeapDay,
        private readonly bool $hasLeapMonth,
        private readonly bool $hasLeapDay,
        private readonly bool $jesthHasThirtyDays,
        private readonly int $lerngSakWeekday,
        private readonly LunarDateLerngSak $lerngSakDate,
        private readonly array $sotins,
        private readonly SolarTimeOfNewYear $timeOfNewYear
    ) {
    }

    public function aharkun(): int
    {
        return $this->aharkun;
    }

    public function kromathupul(): int
    {
        return $this->kromathupul;
    }

    public function avoman(): int
    {
        return $this->avoman;
    }

    public function bodithey(): int
    {
        return $this->bodithey;
    }

    public function hasSolarLeapDay(): bool
    {
        return $this->hasSolarLeapDay;
    }

    public function hasLeapMonth(): bool
    {
        return $this->hasLeapMonth;
    }

    public function hasLeapDay(): bool
    {
        return $this->hasLeapDay;
    }

    public function jesthHasThirtyDays(): bool
    {
        return $this->jesthHasThirtyDays;
    }

    public function lerngSakWeekday(): int
    {
        return $this->lerngSakWeekday;
    }

    public function lerngSakDate(): LunarDateLerngSak
    {
        return $this->lerngSakDate;
    }

    /**
     * @return SolarNewYearDay[]
     */
    public function sotins(): array
    {
        return $this->sotins;
    }

    public function timeOfNewYear(): SolarTimeOfNewYear
    {
        return $this->timeOfNewYear;
    }
}

