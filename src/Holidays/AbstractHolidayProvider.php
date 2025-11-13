<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Holidays;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\Contracts\ConfigurableHolidayProviderInterface;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Support\HolidayTranslator;
use Lisoing\Calendar\ValueObjects\Holiday;
use Lisoing\Calendar\ValueObjects\HolidayCollection;

abstract class AbstractHolidayProvider implements ConfigurableHolidayProviderInterface, HolidayProviderInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $settings = [];

    public static function make(): static
    {
        if (function_exists('app')) {
            /** @var static $resolved */
            $resolved = app(static::class);

            return $resolved;
        }

        return new static();
    }

    abstract public function countryCode(): string;

    abstract public function name(): string;

    /**
     * @return array<int, array<string, string>>
     */
    abstract protected function definitions(int $year): array;

    public function configure(array $settings): void
    {
        $this->settings = $settings;
    }

    public function holidaysForYear(int $year, string $locale): HolidayCollection
    {
        $collection = new HolidayCollection;

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

        $slug = $definition['slug'] ?? $definition['id'];
        $translation = $this->resolveTranslation($definition, $slug, $locale);

        return new Holiday(
            identifier: $definition['id'],
            name: $translation,
            date: $date,
            country: $this->countryCode(),
            locale: $locale,
            metadata: $this->resolveMetadata($definition, $slug)
        );
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings, $key, $default);
    }

    /**
     * @param  array<string, string>  $definition
     * @return array<string, mixed>
     */
    protected function resolveMetadata(array $definition, string $slug): array
    {
        $base = ['type' => $definition['type'] ?? 'public'];

        $extras = $this->setting("observances.$slug.metadata", []);

        return array_merge($base, is_array($extras) ? $extras : []);
    }

    /**
     * @param  array<string, string>  $definition
     */
    protected function resolveNameKey(array $definition, string $slug): string
    {
        $override = $this->setting("observances.$slug.name_key");

        if (is_string($override) && $override !== '') {
            return $override;
        }

        if (isset($definition['name_key']) && is_string($definition['name_key']) && $definition['name_key'] !== '') {
            return $definition['name_key'];
        }

        return $slug;
    }

    protected function resolveDateOverride(string $slug, int $year, string $default): string
    {
        $override = $this->setting("observances.$slug.date");

        if (is_string($override) && $override !== '') {
            if (str_contains($override, '%')) {
                return sprintf($override, $year);
            }

            if (preg_match('/^\d{2}-\d{2}$/', $override) === 1) {
                return sprintf('%d-%s', $year, $override);
            }

            return $override;
        }

        if (is_callable($override)) {
            $result = $override($year, $default);

            if (is_string($result) && $result !== '') {
                return $result;
            }
        }

        return $default;
    }

    /**
     * @param  array<string, string>  $definition
     */
    private function resolveTranslation(array $definition, string $slug, string $locale): string
    {
        $nameKey = $this->resolveNameKey($definition, $slug);

        if (str_contains($nameKey, '::')) {
            return (string) Lang::get($nameKey, [], $locale);
        }

        $fallbackLocale = Config::get('calendar.fallback_locale');
        $fallback = is_string($fallbackLocale) ? $fallbackLocale : 'en';

        return HolidayTranslator::translate(
            directory: $this->countryDirectory(),
            key: $nameKey,
            locale: $locale,
            fallbackLocale: $fallback
        );
    }

    protected function countryDirectory(): string
    {
        return strtolower($this->countryCode());
    }
}
