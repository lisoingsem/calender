<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Formatting;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Lisoing\Calendar\Exceptions\FormatterNotFoundException;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class FormatterManager
{
    /**
     * @var array<string, FormatterInterface>
     */
    private array $instances = [];

    /**
     * @param  array{formatters?: array<string, class-string<FormatterInterface>>}  $config
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $config
    ) {}

    public function format(CalendarDate $date, ?string $locale = null): string
    {
        return $this->formatter($date->getCalendar())->format($date, $locale);
    }

    public function formatter(string $calendarIdentifier): FormatterInterface
    {
        $key = strtolower($calendarIdentifier);

        if (! array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $this->resolveFormatter($key);
        }

        return $this->instances[$key];
    }

    private function resolveFormatter(string $calendarIdentifier): FormatterInterface
    {
        $map = Arr::get($this->config, 'formatters', []);

        $formatterClass = $map[$calendarIdentifier] ?? null;

        if ($formatterClass === null) {
            throw FormatterNotFoundException::make($calendarIdentifier, array_keys($map));
        }

        /** @var mixed $resolved */
        $resolved = $this->container->make($formatterClass);

        if (! $resolved instanceof FormatterInterface) {
            throw new BindingResolutionException(sprintf(
                'Formatter class [%s] must implement [%s].',
                $formatterClass,
                FormatterInterface::class
            ));
        }

        return $resolved;
    }
}
