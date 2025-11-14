<?php

declare(strict_types=1);

namespace Lisoing\Countries;

use InvalidArgumentException;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;

abstract class Country
{
    protected const CALENDAR = null;

    protected ?string $region;

    final protected function __construct(?string $region = null)
    {
        $this->region = $region;
    }

    public static function make(?string $region = null): static
    {
        static::register();

        return new static($region);
    }

    public static function code(): string
    {
        return strtoupper(static::countryCode());
    }

    public static function calendar(): string
    {
        return static::CALENDAR ?? static::code();
    }

    public static function register(): void
    {
        Registry::register(static::code(), static::class);
    }

    public static function find(string $code): ?string
    {
        return Registry::find($code);
    }

    public static function findOrFail(string $code): string
    {
        $resolved = static::find($code);

        if ($resolved === null) {
            throw new InvalidArgumentException(sprintf('Country [%s] is not registered.', $code));
        }

        return $resolved;
    }

    public static function has(string $code): bool
    {
        return static::find($code) !== null;
    }

    public function provider(): HolidayProviderInterface
    {
        $class = static::providerClass();

        if (function_exists('app')) {
            /** @var HolidayProviderInterface $instance */
            $instance = app($class);

            return $instance;
        }

        return new $class();
    }

    public function region(): ?string
    {
        return $this->region;
    }

    abstract public static function defaultLocale(): ?string;

    abstract protected static function providerClass(): string;

    abstract protected static function countryCode(): string;

}

