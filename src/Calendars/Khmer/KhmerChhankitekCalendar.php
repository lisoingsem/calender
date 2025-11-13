<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars\Khmer;

use Asorasoft\Chhankitek\Calendar\Constant;
use Asorasoft\Chhankitek\Calendar\LunarDate;
use Asorasoft\Chhankitek\Chhankitek;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Lisoing\Calendar\Calendars\AbstractCalendar;
use Lisoing\Calendar\Contracts\ConfigurableCalendarInterface;
use Lisoing\Calendar\ValueObjects\CalendarDate;
use RuntimeException;
use Throwable;

final class KhmerChhankitekCalendar extends AbstractCalendar implements ConfigurableCalendarInterface
{
    private string $identifier = 'khmer_chhankitek';

    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    private ?Constant $constants = null;

    private string $timezone = 'Asia/Phnom_Penh';

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function configure(array $settings): void
    {
        $this->settings = $settings;

        if (isset($settings['identifier']) && is_string($settings['identifier'])) {
            $this->identifier = $settings['identifier'];
        }

        $timezone = Arr::get($settings, 'metadata.default_timezone');

        if (is_string($timezone) && $timezone !== '') {
            $this->timezone = $timezone;
        }
    }

    public function toDateTime(CalendarDate $date): CarbonInterface
    {
        $cacheKey = $this->cacheKey('to_datetime', [
            'year' => $date->getYear(),
            'month' => $date->getMonth(),
            'day' => $date->getDay(),
        ]);

        return $this->remember($cacheKey, function () use ($date): CarbonImmutable {
            $mapping = $this->lookupReference($date->getYear(), $date->getMonth(), $date->getDay());

            if ($mapping !== null) {
                return $this->makeCarbon($mapping);
            }

            $hint = $date->getContextValue('gregorian_hint');

            if ($hint instanceof CarbonInterface) {
                return CarbonImmutable::instance($hint);
            }

            if (is_string($hint)) {
                return $this->makeCarbon($hint);
            }

            return CarbonImmutable::instance(parent::toDateTime($date));
        });
    }

    public function fromDateTime(CarbonInterface $dateTime): CalendarDate
    {
        $cacheKey = $this->cacheKey('from_datetime', [
            'date' => $dateTime->setTimezone($this->timezone)->toDateString(),
        ]);

        /** @var CalendarDate $result */
        $result = $this->remember($cacheKey, function () use ($dateTime): CalendarDate {
            $gregorian = CarbonImmutable::instance($dateTime)->setTimezone($this->timezone);
            $match = $this->reverseLookup($gregorian);

            if ($match !== null) {
                [$year, $month, $day] = $match;

                $calendarDate = new CalendarDate($year, $month, $day, $this->identifier);

                return $this->enrichContext($calendarDate, $gregorian);
            }

            $calendarDate = parent::fromDateTime($gregorian);

            return $this->enrichContext(
                $calendarDate->withContext('approximate', true),
                $gregorian
            );
        });

        return $result;
    }

    /**
     * @return array<int, mixed>|null
     */
    private function reverseLookup(CarbonInterface $dateTime): ?array
    {
        /** @var array<int, array<string, string>> $reference */
        $reference = Arr::get($this->settings, 'reference_dates', []);

        foreach ($reference as $year => $mappings) {
            foreach ($mappings as $key => $gregorianDate) {
                $candidate = $this->makeCarbon($gregorianDate);

                if ($candidate->isSameDay($dateTime)) {
                    $segments = explode('-', $key);

                    if (count($segments) !== 2) {
                        throw new RuntimeException(sprintf('Invalid lunar reference key [%s].', $key));
                    }

                    return [
                        (int) $year,
                        (int) $segments[0],
                        (int) $segments[1],
                    ];
                }
            }
        }

        return null;
    }

    private function lookupReference(int $year, int $month, int $day): ?string
    {
        $key = sprintf('%02d-%02d', $month, $day);

        /** @var string|null $mapping */
        $mapping = Arr::get($this->settings, sprintf('reference_dates.%d.%s', $year, $key));

        return $mapping;
    }

    private function makeCarbon(string $date): CarbonImmutable
    {
        if ($this->timezone !== '') {
            return CarbonImmutable::parse($date, $this->timezone);
        }

        return CarbonImmutable::parse($date);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function cacheKey(string $channel, array $payload): string
    {
        if (! $this->isCachingEnabled()) {
            return uniqid($channel, true);
        }

        $prefix = Arr::get($this->settings, 'cache.prefix', 'calendar:khmer');

        return sprintf(
            '%s:%s:%s',
            $prefix,
            $channel,
            hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR))
        );
    }

    /**
     * @template TValue
     *
     * @param  callable():TValue  $callback
     * @return TValue
     */
    private function remember(string $key, callable $callback)
    {
        if (! $this->isCachingEnabled()) {
            /** @var mixed $result */
            $result = $callback();

            return $result;
        }

        $ttl = (int) Arr::get($this->settings, 'cache.ttl', 0);

        if ($ttl <= 0) {
            throw new RuntimeException('Cache TTL must be greater than zero when caching is enabled.');
        }

        return Cache::remember($key, $ttl, $callback);
    }

    private function isCachingEnabled(): bool
    {
        return (bool) Arr::get($this->settings, 'cache.enabled', false);
    }

    private function enrichContext(CalendarDate $date, CarbonImmutable $gregorian): CalendarDate
    {
        $moonPhase = $date->getDay() <= 15 ? 'keit' : 'roaj';

        $enriched = $date
            ->withContext('phase', $moonPhase)
            ->withContext('leap_information', Arr::get($this->settings, sprintf('leap_years.%d', $date->getYear()), []))
            ->withContext('gregorian_hint', $gregorian);

        try {
            $engine = new Chhankitek($gregorian);
            $lunar = $engine->findLunarDate($gregorian);
            $moon = $engine->getKhmerLunarDay($lunar->getDay());

            $enriched = $enriched
                ->withContext('moon_day', $moon->getMoonCount())
                ->withContext('moon_status_index', $moon->getMoonStatus())
                ->withContext('khmer_month_label', $this->resolveMonthLabel($lunar->getMonth()))
                ->withContext('buddhist_era_year', $engine->getBEYear($gregorian))
                ->withContext('animal_cycle_index', $engine->getAnimalYear($gregorian))
                ->withContext('jolak_sakaraj', $engine->getJolakSakarajYear($gregorian));
        } catch (Throwable) {
            // Ignore enrichment failures; base context still useful.
        }

        return $enriched;
    }

    private function resolveMonthLabel(int $index): ?string
    {
        $constants = $this->constants();

        foreach ($constants->getLunarMonths() as $label => $value) {
            if ($value === $index) {
                return $label;
            }
        }

        return null;
    }

    private function constants(): Constant
    {
        return $this->constants ??= new Constant();
    }
}
