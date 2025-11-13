<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

final class SolarResidual
{
    public function __construct(
        private readonly int $reasey,
        private readonly int $angsar,
        private readonly int $libda
    ) {
    }

    public function reasey(): int
    {
        return $this->reasey;
    }

    public function angsar(): int
    {
        return $this->angsar;
    }

    public function libda(): int
    {
        return $this->libda;
    }
}

