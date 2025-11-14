<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

final class LunarDateLerngSak
{
    public function __construct(
        private readonly int $day,
        private readonly int $month
    ) {
    }

    public function day(): int
    {
        return $this->day;
    }

    public function month(): int
    {
        return $this->month;
    }
}

