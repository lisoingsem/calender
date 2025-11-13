<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Unit;

use Carbon\CarbonImmutable;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Calendar\ValueObjects\HolidayCollection;

final class HolidayCollectionTest extends TestCase
{
    public function test_it_counts_and_iterates_holidays(): void
    {
        $collection = new HolidayCollection;

        $collection->add(new Holiday(
            identifier: 'sample',
            name: 'Sample Holiday',
            date: CarbonImmutable::create(2025, 1, 1),
            country: 'KH',
            locale: 'en'
        ));

        $this->assertCount(1, $collection);
        $this->assertSame(1, iterator_count($collection->getIterator()));
    }
}
