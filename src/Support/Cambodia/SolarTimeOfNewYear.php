<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

final class SolarTimeOfNewYear
{
    public function __construct(
        private readonly int $hour,
        private readonly int $minute
    ) {
    }

    public function hour(): int
    {
        return $this->hour;
    }

    public function minute(): int
    {
        return $this->minute;
    }
}

