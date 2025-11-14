<?php

declare(strict_types=1);

namespace Lisoing\Holidays;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Countries\Country;

final class Holidays
{
    private function __construct(
        private readonly string $countryCode,
        private readonly int $year,
        private readonly ?string $locale
    ) {}

    /**
     * @param  HolidayProviderInterface|Country|string  $country
     */
    public static function for(HolidayProviderInterface|Country|string $country, ?int $year = null, ?string $locale = null): self
    {
        $year ??= CarbonImmutable::now()->year;

        return new self(
            countryCode: self::resolveCountryCode($country),
            year: $year,
            locale: $locale
        );
    }

    /**
     * @param  HolidayProviderInterface|Country|string  $country
     */
    public static function has(HolidayProviderInterface|Country|string $country): bool
    {
        return self::manager()->provider(self::resolveCountryCode($country)) !== null;
    }

    /**
     * @param  HolidayProviderInterface|Country|string|null  $country
     * @return array<int, array{name: string, date: CarbonImmutable}>
     */
    public function get(HolidayProviderInterface|Country|string|null $country = null, ?int $year = null): array
    {
        $code = $country !== null ? self::resolveCountryCode($country) : $this->countryCode;
        $year ??= $this->year;

        return array_map(
            static fn (Holiday $holiday): array => [
                'name' => $holiday->name(),
                'date' => $holiday->date(),
            ],
            self::manager()->forCountry($year, $code, $this->locale)->all()
        );
    }

    /**
     * @param  HolidayProviderInterface|Country|string|null  $country
     */
    public function getInRange(
        CarbonInterface|string $from,
        CarbonInterface|string $to,
        HolidayProviderInterface|Country|string|null $country = null
    ): array {
        [$start, $end] = self::normalizeRange($from, $to);
        $code = $country !== null ? self::resolveCountryCode($country) : $this->countryCode;

        return array_values(array_filter(
            $this->get($code, $this->year),
            static fn (array $entry): bool => $entry['date']->betweenIncluded($start, $end)
        ));
    }

    /**
     * @param  HolidayProviderInterface|Country|string|null  $country
     */
    public function isHoliday(
        CarbonInterface|string $date,
        HolidayProviderInterface|Country|string|null $country = null
    ): bool {
        $candidate = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);
        $code = $country !== null ? self::resolveCountryCode($country) : $this->countryCode;
        $target = $candidate->format('Y-m-d');

        foreach (self::manager()->forCountry($candidate->year, $code, $this->locale) as $holiday) {
            if ($holiday->date()->format('Y-m-d') === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  HolidayProviderInterface|Country|string|null  $country
     */
    public function getName(
        CarbonInterface|string $date,
        HolidayProviderInterface|Country|string|null $country = null
    ): ?string {
        $candidate = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);
        $code = $country !== null ? self::resolveCountryCode($country) : $this->countryCode;
        $target = $candidate->format('Y-m-d');

        foreach (self::manager()->forCountry($candidate->year, $code, $this->locale) as $holiday) {
            if ($holiday->date()->format('Y-m-d') === $target) {
                return $holiday->name();
            }
        }

        return null;
    }

    /**
     * @param  HolidayProviderInterface|Country|string  $country
     */
    private static function resolveCountryCode(HolidayProviderInterface|Country|string $country): string
    {
        if (is_string($country)) {
            return strtoupper($country);
        }

        if ($country instanceof Country) {
            return $country::code();
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

