<?php

declare(strict_types=1);

namespace Lisoing\Countries;

final class Registry
{
    /**
     * @var array<string, class-string<Country>>
     */
    private static array $countries = [];

    public static function register(string $code, string $countryClass): void
    {
        self::$countries[strtoupper($code)] = $countryClass;
    }

    /**
     * @return array<string, class-string<Country>>
     */
    public static function all(): array
    {
        return self::$countries;
    }

    public static function find(string $code): ?string
    {
        $code = strtoupper($code);

        return self::$countries[$code] ?? null;
    }

    public static function has(string $code): bool
    {
        return self::find($code) !== null;
    }

    public static function resolve(string $code, ?string $region = null): Country
    {
        $class = self::find($code);

        if ($class === null) {
            throw new \InvalidArgumentException(sprintf('Country [%s] is not supported.', $code));
        }

        /** @var Country $instance */
        $instance = $class::make($region);

        return $instance;
    }
}

