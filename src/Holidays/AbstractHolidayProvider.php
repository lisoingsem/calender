<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Holidays;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Calendar\ValueObjects\HolidayCollection;

abstract class AbstractHolidayProvider implements HolidayProviderInterface
{
    abstract public function countryCode(): string;

    abstract public function name(): string;

    /**
     * @return array<int, array<string, string>>
     */
    abstract protected function definitions(int $year): array;

    public function holidaysForYear(int $year, string $locale): HolidayCollection
    {
        $collection = new HolidayCollection();

        foreach ($this->definitions($year) as $definition) {
            $collection->add($this->makeHoliday($definition, $locale));
        }

        return $collection;
    }

    /**
     * @param  array<string, string>  $definition
     */
    protected function makeHoliday(array $definition, string $locale): Holiday
    {
        $date = CarbonImmutable::parse($definition['date']);

        $nameKey = $definition['name_key'];

        $translation = Lang::get($nameKey, [], $locale);

        if ($translation === $nameKey) {
            $translation = Lang::get($nameKey, [], config('calendar.fallback_locale'));
        }

        return new Holiday(
            identifier: $definition['id'],
            name: $translation,
            date: $date,
            country: $this->countryCode(),
            locale: $locale,
            metadata: [
                'type' => $definition['type'] ?? 'public',
            ]
        );
    }
}

