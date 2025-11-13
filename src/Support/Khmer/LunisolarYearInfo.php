<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

final class LunisolarYearInfo
{
    public function __construct(
        private int $aharkun,
        private readonly int $kromathupul,
        private readonly int $avoman,
        private int $bodithey
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

    public function setBodithey(int $value): void
    {
        $this->bodithey = $value;
    }

    public function incrementAharkun(int $value): void
    {
        $this->aharkun += $value;
    }
}

