<?php

declare(strict_types=1);

namespace Lisoing\Calendar\ValueObjects;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Wrapper class for single or multiple holidays.
 * Provides convenient methods to access holiday information.
 */
final class HolidayResult implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * @param  array<int, Holiday>  $holidays
     */
    public function __construct(
        private readonly array $holidays = []
    ) {}

    /**
     * Convert to array representation.
     * Implements Arrayable interface.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (Holiday $holiday): array => [
                'id' => $holiday->identifier(),
                'name' => $holiday->name(),
                'title' => $holiday->name(),
                'date' => $holiday->date()->format('Y-m-d'),
                'country' => $holiday->country(),
                'locale' => $holiday->locale(),
                'metadata' => $holiday->metadata(),
            ],
            $this->holidays
        );
    }

    /**
     * Get holiday title/name (first if multiple).
     */
    public function title(): ?string
    {
        $first = $this->first();

        return $first?->name();
    }

    /**
     * Get holiday name (alias for title()).
     */
    public function name(): ?string
    {
        return $this->title();
    }

    /**
     * Get holiday date (first if multiple).
     */
    public function date(): ?CarbonInterface
    {
        $first = $this->first();

        return $first?->date();
    }

    /**
     * Get merged metadata from all holidays.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        if (count($this->holidays) === 0) {
            return [];
        }

        if (count($this->holidays) === 1) {
            return $this->holidays[0]->metadata();
        }

        // Merge metadata from all holidays
        $merged = [];
        foreach ($this->holidays as $holiday) {
            $meta = $holiday->metadata();
            foreach ($meta as $key => $value) {
                if (! isset($merged[$key])) {
                    $merged[$key] = $value;
                } elseif (is_array($merged[$key]) && is_array($value)) {
                    $merged[$key] = array_merge($merged[$key], $value);
                } elseif (is_array($merged[$key])) {
                    $merged[$key][] = $value;
                } else {
                    $merged[$key] = [$merged[$key], $value];
                }
            }
        }

        return $merged;
    }

    /**
     * Get all holidays as array.
     *
     * @return array<int, Holiday>
     */
    public function all(): array
    {
        return $this->holidays;
    }

    /**
     * Get first holiday.
     */
    public function first(): ?Holiday
    {
        return $this->holidays[0] ?? null;
    }

    /**
     * Count of holidays.
     */
    public function count(): int
    {
        return count($this->holidays);
    }

    /**
     * Check if result has any holidays.
     */
    public function isEmpty(): bool
    {
        return count($this->holidays) === 0;
    }

    /**
     * Convert the object to its JSON representation (implements Jsonable).
     *
     * @param  int  $options
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Specify data which should be serialized to JSON (implements JsonSerializable).
     *
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert to string representation.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}

