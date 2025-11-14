<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

final class SolarSunInfo
{
    public function __construct(
        private readonly int $averageAsLibda,
        private readonly int $khan,
        private readonly int $pouichalip,
        private readonly SolarPhol $phol,
        private readonly int $inaugurationAsLibda
    ) {
    }

    public function averageAsLibda(): int
    {
        return $this->averageAsLibda;
    }

    public function khan(): int
    {
        return $this->khan;
    }

    public function pouichalip(): int
    {
        return $this->pouichalip;
    }

    public function phol(): SolarPhol
    {
        return $this->phol;
    }

    public function inaugurationAsLibda(): int
    {
        return $this->inaugurationAsLibda;
    }
}

