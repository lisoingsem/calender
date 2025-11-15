<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Holidays;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Support\LocaleResolver;
use Lisoing\Calendar\ValueObjects\HolidayCollection;

final class HolidayManager
{
    /**
     * @var Collection<string, HolidayProviderInterface>
     */
    private Collection $providers;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $fallbackLocale,
        private readonly array $config = []
    ) {
        /** @var Collection<string, HolidayProviderInterface> $providers */
        $providers = collect();

        $this->providers = $providers;
    }

    public function register(string $countryCode, HolidayProviderInterface $provider): void
    {
        $this->providers->put(strtoupper($countryCode), $provider);
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

        $locale = $this->resolveLocale($locale);

        return $provider->holidaysForYear($year, $locale);
    }

    public function translate(string $key, ?string $locale = null): string
    {
        $locale = $this->resolveLocale($locale);

        return Lang::get($key, [], $locale);
    }

    private function resolveLocale(?string $locale): string
    {
        // If locale is explicitly provided, use it (canonicalized)
        if ($locale !== null && $locale !== '') {
            $canonicalLocale = LocaleResolver::canonicalize($locale);
            if ($canonicalLocale !== '') {
                return $canonicalLocale;
            }
        }

        // Otherwise, prioritize Laravel's app locale (like CalendarManager does)
        // This ensures that when app()->getLocale() returns 'km', it will be used
        // even if 'km' is not in the supported_locales config
        $appLocale = LocaleResolver::canonicalize((string) App::getLocale());
        $appFallback = LocaleResolver::canonicalize((string) Config::get('app.fallback_locale'));
        $default = LocaleResolver::canonicalize($this->fallbackLocale) ?: 'en';

        return $appLocale ?: $appFallback ?: $default;
    }
}
