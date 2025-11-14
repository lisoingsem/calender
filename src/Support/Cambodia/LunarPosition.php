<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

use Carbon\CarbonImmutable;

final class LunarPosition
{
    public function __construct(
        private readonly int $dayIndex,
        private readonly int $monthIndex,
        private readonly CarbonImmutable $referenceDate
    ) {
    }

    public function day(): int
    {
        return $this->dayIndex;
    }

    public function month(): int
    {
        return $this->monthIndex;
    }

    public function referenceDate(): CarbonImmutable
    {
        return $this->referenceDate;
    }
}

