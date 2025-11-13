<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Contracts;

use Lisoing\Calendar\ValueObjects\HolidayCollection;

interface HolidayProviderInterface
{
    /**
     * ISO 3166 alpha-2 country code.
     */
    public function countryCode(): string;

    public function name(): string;

    public function holidaysForYear(int $year, string $locale): HolidayCollection;
}

