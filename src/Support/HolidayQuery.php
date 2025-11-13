<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\ValueObjects\Holiday;

final class HolidayQuery
{
    private function __construct(
        private readonly string $countryCode,
        private readonly int $year,
        private readonly ?string $locale
    ) {}

    /**
     * @param  HolidayProviderInterface|string  $country
     */
    public static function for(HolidayProviderInterface|string $country, ?int $year = null, ?string $locale = null): self
    {
        $year ??= CarbonImmutable::now()->year;

        return new self(
            countryCode: self::resolveCountryCode($country),
            year: $year,
            locale: $locale
        );
    }

    /**
     * @param  HolidayProviderInterface|string  $country
     */
    public static function has(HolidayProviderInterface|string $country): bool
    {
        $code = self::resolveCountryCode($country);

        return self::manager()->provider($code) !== null;
    }

    /**
     * @param  HolidayProviderInterface|string|null  $country
     * @return array<int, array{name: string, date: CarbonImmutable}>
     */
    public function get(HolidayProviderInterface|string|null $country = null, ?int $year = null): array
    {
        $countryCode = $country !== null
            ? self::resolveCountryCode($country)
            : $this->countryCode;

        $year ??= $this->year;

        $holidays = self::manager()
            ->forCountry($year, $countryCode, $this->locale)
            ->all();

        return array_map(static function (Holiday $holiday): array {
            return [
                'name' => $holiday->name(),
                'date' => $holiday->date(),
            ];
        }, $holidays);
    }

    /**
     * @param  HolidayProviderInterface|string|null  $country
     */
    public function getInRange(
        CarbonInterface|string $from,
        CarbonInterface|string $to,
        HolidayProviderInterface|string|null $country = null
    ): array {
        $collection = self::manager()
            ->forCountry($this->year, $country ? self::resolveCountryCode($country) : $this->countryCode, $this->locale)
            ->all();

        [$start, $end] = self::normalizeRange($from, $to);

        return array_values(array_filter(
            array_map(static fn (Holiday $holiday): array => [
                'name' => $holiday->name(),
                'date' => $holiday->date(),
            ], $collection),
            static fn (array $entry): bool => $entry['date']->betweenIncluded($start, $end)
        ));
    }

    /**
     * @param  HolidayProviderInterface|string|null  $country
     */
    public function isHoliday(
        CarbonInterface|string $date,
        HolidayProviderInterface|string|null $country = null
    ): bool {
        $candidate = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);
        $countryCode = $country ? self::resolveCountryCode($country) : $this->countryCode;

        $holidays = self::manager()
            ->forCountry($candidate->year, $countryCode, $this->locale)
            ->all();

        $target = $candidate->format('Y-m-d');

        foreach ($holidays as $holiday) {
            if ($holiday->date()->format('Y-m-d') === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  HolidayProviderInterface|string|null  $country
     */
    public function getName(
        CarbonInterface|string $date,
        HolidayProviderInterface|string|null $country = null
    ): ?string {
        $candidate = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);
        $countryCode = $country ? self::resolveCountryCode($country) : $this->countryCode;
        $holidays = self::manager()
            ->forCountry($candidate->year, $countryCode, $this->locale)
            ->all();

        $target = $candidate->format('Y-m-d');

        foreach ($holidays as $holiday) {
            if ($holiday->date()->format('Y-m-d') === $target) {
                return $holiday->name();
            }
        }

        return null;
    }

    private static function resolveCountryCode(HolidayProviderInterface|string $country): string
    {
        if (is_string($country)) {
            return strtoupper($country);
        }

        return strtoupper($country->countryCode());
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function normalizeRange(CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        $start = $from instanceof CarbonImmutable ? $from : CarbonImmutable::parse($from);
        $end = $to instanceof CarbonImmutable ? $to : CarbonImmutable::parse($to);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private static function manager(): HolidayManager
    {
        /** @var HolidayManager $manager */
        $manager = app(HolidayManager::class);

        return $manager;
    }
}

