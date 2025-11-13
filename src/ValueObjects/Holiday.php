<?php

declare(strict_types=1);

namespace Lisoing\Calendar\ValueObjects;

use Carbon\CarbonInterface;

final class Holiday
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $name,
        private readonly CarbonInterface $date,
        private readonly string $country,
        private readonly string $locale,
        private readonly array $metadata = []
    ) {
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function date(): CarbonInterface
    {
        return $this->date;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}

