<?php

declare(strict_types=1);

namespace Lisoing\Calendar\ValueObjects;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Holiday>
 */
final class HolidayCollection implements Countable, IteratorAggregate
{
    /**
     * @param  array<int, Holiday>  $holidays
     */
    public function __construct(private array $holidays = []) {}

    /**
     * @param  array<int, Holiday>  $holidays
     */
    public static function make(array $holidays): self
    {
        return new self($holidays);
    }

    public function add(Holiday $holiday): void
    {
        $this->holidays[] = $holiday;
    }

    /**
     * @return ArrayIterator<int, Holiday>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->holidays);
    }

    public function count(): int
    {
        return count($this->holidays);
    }

    /**
     * @return array<int, Holiday>
     */
    public function all(): array
    {
        return $this->holidays;
    }
}
