<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Holidays;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Calendar\ValueObjects\HolidayCollection;

final class HolidayManager
{
    /**
     * @var Collection<string, HolidayProviderInterface>
     */
    private Collection $providers;

    public function __construct(
        private readonly string $fallbackLocale
    ) {
        $this->providers = collect();
    }

    public function register(HolidayProviderInterface $provider): void
    {
        $this->providers->put($provider->countryCode(), $provider);
    }

    public function provider(string $countryCode): ?HolidayProviderInterface
    {
        return $this->providers->get(strtoupper($countryCode));
    }

    public function forCountry(int $year, string $countryCode, ?string $locale = null): HolidayCollection
    {
        $countryCode = strtoupper($countryCode);

        $provider = $this->provider($countryCode);

        if ($provider === null) {
            return new HolidayCollection;
        }

        $locale ??= $this->resolveLocale();

        return $provider->holidaysForYear($year, $locale);
    }

    public function translate(string $key, ?string $locale = null): string
    {
        $locale ??= $this->resolveLocale();

        return Lang::get($key, [], $locale);
    }

    private function resolveLocale(): string
    {
        $locale = App::getLocale();

        if ($locale === null || $locale === '') {
            return $this->fallbackLocale;
        }

        if (Lang::has('calendar::holidays.placeholder', [], $locale)) {
            return $locale;
        }

        return Config::get('app.fallback_locale', $this->fallbackLocale);
    }

    public function wrap(string $identifier, string $country, string $nameKey, string $locale, \DateTimeInterface $date): Holiday
    {
        $translation = Lang::get($nameKey, [], $locale);

        if ($translation === $nameKey) {
            $translation = Lang::get($nameKey, [], $this->fallbackLocale);
        }

        return new Holiday(
            identifier: $identifier,
            name: $translation,
            date: CarbonImmutable::instance($date),
            country: $country,
            locale: $locale
        );
    }
}
