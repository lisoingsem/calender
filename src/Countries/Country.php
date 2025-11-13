<?php

declare(strict_types=1);

namespace Lisoing\Countries;

use Lisoing\Calendar\Contracts\HolidayProviderInterface;

abstract class Country
{
    public static function make(): HolidayProviderInterface
    {
        $providerClass = static::providerClass();

        if (function_exists('app')) {
            /** @var HolidayProviderInterface $instance */
            $instance = app($providerClass);

            return $instance;
        }

        return new $providerClass();
    }

    public static function code(): string
    {
        return strtoupper(static::countryCode());
    }

    abstract protected static function providerClass(): string;

    abstract protected static function countryCode(): string;
}

