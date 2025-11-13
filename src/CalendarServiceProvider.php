<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Contracts\ConfigurableCalendarInterface;
use Lisoing\Calendar\Contracts\ConfigurableHolidayProviderInterface;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Formatting\FormatterManager;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\Support\CalendarToolkit;

final class CalendarServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/calendar.php', 'calendar');

        $this->app->singleton(CalendarManager::class, static function (Container $container): CalendarManager {
            $config = $container->make('config')->get('calendar');

            $manager = new CalendarManager(
                defaultCalendar: $config['default_calendar'],
                fallbackLocale: $config['fallback_locale']
            );

            $calendars = Arr::get($config, 'calendars', []);
            $settings = Arr::get($config, 'calendar_settings', []);

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

        $this->app->singleton(FormatterManager::class, static function (Container $container): FormatterManager {
            $config = $container->make('config')->get('calendar');

            return new FormatterManager(
                container: $container,
                config: [
                    'formatters' => Arr::get($config, 'formatters', []),
                ]
            );
        });

        $this->app->bind('calendar.formatter', static fn (Container $container): FormatterManager => $container->make(FormatterManager::class));

        $calendarConfig = $this->app->make('config')->get('calendar');
        $holidaysEnabled = (bool) Arr::get($calendarConfig, 'features.holidays.enabled', true);

        if ($holidaysEnabled) {
            $this->registerHolidayManager();
        }

        $this->registerToolkit();
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/calendar.php' => config_path('calendar.php'),
        ], 'calendar-config');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'calendar');
    }

    private function registerHolidayManager(): void
    {
        $this->app->singleton(HolidayManager::class, static function (Container $container): HolidayManager {
            $config = $container->make('config')->get('calendar');

            $manager = new HolidayManager(
                fallbackLocale: $config['fallback_locale'],
                config: [
                    'supported_locales' => Arr::get($config, 'supported_locales', []),
                ]
            );

            $providers = Arr::get($config, 'countries', []);
            $settings = Arr::get($config, 'holiday_settings', []);

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
            $config = $container->make('config')->get('calendar');
            $features = Arr::get($config, 'features', []);
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
}
