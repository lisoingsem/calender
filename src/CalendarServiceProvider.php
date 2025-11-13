<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Lisoing\Calendar\Calendars\CambodiaCalendar;
use Lisoing\Calendar\Calendars\GregorianCalendar;
use Lisoing\Calendar\Calendars\NepalCalendar;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Contracts\ConfigurableCalendarInterface;
use Lisoing\Calendar\Contracts\ConfigurableHolidayProviderInterface;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Formatting\FormatterManager;
use Lisoing\Calendar\Formatting\LunarFormatter;
use Lisoing\Calendar\Holidays\Countries\Cambodia;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\Support\CalendarToolkit;

final class CalendarServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->app->singleton(CalendarManager::class, function (Container $container): CalendarManager {
            /** @var \Illuminate\Contracts\Config\Repository $configRepository */
            $configRepository = $container->make('config');
            $config = $configRepository->get('calendar', []);

            $defaultCalendar = Arr::get($config, 'default_calendar', 'gregorian');

            $fallbackLocale = Arr::get($config, 'fallback_locale');
            if (! is_string($fallbackLocale) || $fallbackLocale === '') {
                $fallbackLocale = (string) $configRepository->get('app.fallback_locale', $configRepository->get('app.locale', 'en'));
            }

            $manager = new CalendarManager(
                defaultCalendar: (string) $defaultCalendar,
                fallbackLocale: $fallbackLocale
            );

            $calendars = array_merge($this->defaultCalendars(), Arr::get($config, 'calendars', []));
            $settings = array_replace_recursive($this->defaultCalendarSettings(), Arr::get($config, 'calendar_settings', []));

            foreach ($calendars as $identifier => $calendarClass) {
                $calendar = self::resolveCalendar($container, $calendarClass);

                if ($calendar instanceof ConfigurableCalendarInterface) {
                    $calendar->configure($settings[$identifier] ?? []);
                }

                if ($calendar->identifier() !== $identifier) {
                    throw new BindingResolutionException(sprintf(
                        'Configured identifier [%s] does not match calendar class [%s] identifier [%s].',
                        $identifier,
                        $calendarClass,
                        $calendar->identifier()
                    ));
                }

                $manager->register($calendar);
            }

            return $manager;
        });

        $this->app->bind('calendar', static fn (Container $container): CalendarManager => $container->make(CalendarManager::class));

        $this->app->singleton(FormatterManager::class, function (Container $container): FormatterManager {
            $config = $container->make('config')->get('calendar', []);
            $formatters = array_merge($this->defaultFormatters(), Arr::get($config, 'formatters', []));

            return new FormatterManager(
                container: $container,
                config: [
                    'formatters' => $formatters,
                ]
            );
        });

        $this->app->bind('calendar.formatter', static fn (Container $container): FormatterManager => $container->make(FormatterManager::class));

        /** @var \Illuminate\Contracts\Config\Repository $configRepository */
        $configRepository = $this->app->make('config');
        $calendarConfig = $configRepository->get('calendar', []);
        $features = array_replace_recursive($this->defaultFeatures(), Arr::get($calendarConfig, 'features', []));
        $holidaysEnabled = (bool) Arr::get($features, 'holidays.enabled', true);

        if ($holidaysEnabled) {
            $this->registerHolidayManager();
        }

        $this->registerToolkit();
    }

    public function boot(): void {}

    private function registerHolidayManager(): void
    {
        $this->app->singleton(HolidayManager::class, function (Container $container): HolidayManager {
            /** @var \Illuminate\Contracts\Config\Repository $configRepository */
            $configRepository = $container->make('config');
            $config = $configRepository->get('calendar', []);

            $fallbackLocale = Arr::get($config, 'fallback_locale');
            if (! is_string($fallbackLocale) || $fallbackLocale === '') {
                $fallbackLocale = (string) $configRepository->get('app.fallback_locale', $configRepository->get('app.locale', 'en'));
            }

            $supportedLocales = Arr::get($config, 'supported_locales');
            if (! is_array($supportedLocales) || $supportedLocales === []) {
                $supportedLocales = (array) $configRepository->get('app.supported_locales', []);
            }

            $manager = new HolidayManager(
                fallbackLocale: $fallbackLocale,
                config: [
                    'supported_locales' => $supportedLocales,
                ]
            );

            $providers = array_merge($this->defaultCountries(), Arr::get($config, 'countries', []));
            $settings = array_replace_recursive($this->defaultHolidaySettings(), Arr::get($config, 'holiday_settings', []));

            foreach ($providers as $countryCode => $providerClass) {
                $provider = self::resolveHolidayProvider($container, $providerClass);

                if ($provider instanceof ConfigurableHolidayProviderInterface) {
                    $provider->configure($settings[strtoupper((string) $countryCode)] ?? []);
                }

                if (strtoupper((string) $countryCode) !== strtoupper($provider->countryCode())) {
                    throw new BindingResolutionException(sprintf(
                        'Configured country code [%s] does not match provider [%s] identifier [%s].',
                        $countryCode,
                        $providerClass,
                        $provider->countryCode()
                    ));
                }

                $manager->register((string) $countryCode, $provider);
            }

            return $manager;
        });
    }

    private function registerToolkit(): void
    {
        $this->app->singleton(CalendarToolkit::class, function (Container $container): CalendarToolkit {
            /** @var \Illuminate\Contracts\Config\Repository $configRepository */
            $configRepository = $container->make('config');
            $config = $configRepository->get('calendar', []);
            $features = array_replace_recursive($this->defaultFeatures(), Arr::get($config, 'features', []));
            $holidaysEnabled = (bool) Arr::get($features, 'holidays.enabled', true);

            $holidayManager = null;

            if ($holidaysEnabled && $container->bound(HolidayManager::class)) {
                $holidayManager = $container->make(HolidayManager::class);
            }

            return new CalendarToolkit(
                calendarManager: $container->make(CalendarManager::class),
                formatterManager: $container->make(FormatterManager::class),
                holidayManager: $holidayManager,
                features: [
                    'holidays' => [
                        'enabled' => $holidaysEnabled,
                    ],
                ]
            );
        });

        $this->app->alias(CalendarToolkit::class, 'calendar.toolkit');
    }

    /**
     * @param  class-string<CalendarInterface>  $calendarClass
     */
    private static function resolveCalendar(Container $container, string $calendarClass): CalendarInterface
    {
        /** @var mixed $resolved */
        $resolved = $container->make($calendarClass);

        if (! $resolved instanceof CalendarInterface) {
            throw new BindingResolutionException(sprintf(
                'Calendar class [%s] must implement [%s].',
                $calendarClass,
                CalendarInterface::class
            ));
        }

        return $resolved;
    }

    /**
     * @param  class-string<HolidayProviderInterface>  $providerClass
     */
    private static function resolveHolidayProvider(Container $container, string $providerClass): HolidayProviderInterface
    {
        /** @var mixed $resolved */
        $resolved = $container->make($providerClass);

        if (! $resolved instanceof HolidayProviderInterface) {
            throw new BindingResolutionException(sprintf(
                'Holiday provider class [%s] must implement [%s].',
                $providerClass,
                HolidayProviderInterface::class
            ));
        }

        return $resolved;
    }

    /**
     * @return array<string, class-string<CalendarInterface>>
     */
    private function defaultCalendars(): array
    {
        return [
            'gregorian' => GregorianCalendar::class,
            'cambodia_lunisolar' => CambodiaCalendar::class,
            'nepal_gregorian' => NepalCalendar::class,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultCalendarSettings(): array
    {
        return [
            'gregorian' => [
                'timezone' => 'UTC',
            ],
            'cambodia_lunisolar' => [
                'timezone' => 'Asia/Phnom_Penh',
            ],
            'nepal_gregorian' => [
                'timezone' => 'Asia/Kathmandu',
            ],
        ];
    }

    /**
     * @return array<string, class-string<HolidayProviderInterface>>
     */
    private function defaultCountries(): array
    {
        return [
            'KH' => Cambodia::class,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultHolidaySettings(): array
    {
        return [
            'KH' => [
                'observances' => [
                    'khmer_new_year' => [
                        'metadata' => [
                            'notes' => 'Observed over three days according to lunisolar cycle.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, class-string>
     */
    private function defaultFormatters(): array
    {
        return [
            'cambodia_lunisolar' => LunarFormatter::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFeatures(): array
    {
        return [
            'holidays' => [
                'enabled' => true,
            ],
        ];
    }
}
