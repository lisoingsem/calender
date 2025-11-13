<?php

declare(strict_types=1);

namespace Lisoing\Countries;

use Lisoing\Calendar\Holidays\Countries\Cambodia as CambodiaProvider;

final class Cambodia extends Country
{
    protected static function providerClass(): string
    {
        return CambodiaProvider::class;
    }

    protected static function countryCode(): string
    {
        return 'KH';
    }
}

