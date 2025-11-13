<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

use Carbon\CarbonImmutable;

final class LunarDate
{
    public function __construct(
        private readonly CarbonImmutable $gregorianDate,
        private readonly LunarDay $lunarDay,
        private readonly string $lunarMonthSlug,
        private readonly int $buddhistEraYear,
        private readonly int $animalYearIndex,
        private readonly int $eraYearIndex,
        private readonly int $weekdayIndex
    ) {
    }

    public function gregorianDate(): CarbonImmutable
    {
        return $this->gregorianDate;
    }

    public function lunarDay(): LunarDay
    {
        return $this->lunarDay;
    }

    public function lunarMonthSlug(): string
    {
        return $this->lunarMonthSlug;
    }

    public function buddhistEraYear(): int
    {
        return $this->buddhistEraYear;
    }

    public function animalYearIndex(): int
    {
        return $this->animalYearIndex;
    }

    public function eraYearIndex(): int
    {
        return $this->eraYearIndex;
    }

    public function weekdayIndex(): int
    {
        return $this->weekdayIndex;
    }
}

