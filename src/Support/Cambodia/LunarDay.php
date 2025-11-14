<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

final class LunarDay
{
    public function __construct(
        private readonly int $day,
        private readonly string $phaseKey
    ) {
    }

    public function day(): int
    {
        return $this->day;
    }

    public function phaseKey(): string
    {
        return $this->phaseKey;
    }
}

