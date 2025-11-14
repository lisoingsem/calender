<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Lisoing\Calendar\Calendars\GregorianCalendar;
use Lisoing\Calendar\Contracts\CalendarInterface;
use Lisoing\Calendar\Contracts\ConfigurableCalendarInterface;
use Lisoing\Calendar\Contracts\ConfigurableHolidayProviderInterface;
use Lisoing\Calendar\Contracts\HolidayProviderInterface;
use Lisoing\Calendar\Formatting\FormatterManager;
use Lisoing\Calendar\Formatting\LunarFormatter;
use Lisoing\Calendar\Holidays\HolidayManager;
use Lisoing\Calendar\Support\CalendarToolkit;
use Lisoing\Countries\Country;
use Lisoing\Countries\Registry;

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

            // Default calendar is optional - use from config or null
            $defaultCalendar = Arr::get($config, 'default_calendar');
            $defaultCalendar = is_string($defaultCalendar) && $defaultCalendar !== '' ? $defaultCalendar : null;

            // Fallback locale from Laravel app config
            $fallbackLocale = Arr::get($config, 'fallback_locale');
            if (! is_string($fallbackLocale) || $fallbackLocale === '') {
                $fallbackLocale = (string) $configRepository->get('app.fallback_locale', $configRepository->get('app.locale', 'en'));
            }

            $manager = new CalendarManager(
                defaultCalendar: $defaultCalendar,
                fallbackLocale: $fallbackLocale
            );

            // Always register gregorian calendar
            $gregorianCalendar = self::resolveCalendar($container, GregorianCalendar::class);
            
            // Use Laravel's app timezone if not configured
            $appTimezone = (string) $configRepository->get('app.timezone', 'UTC');
            $gregorianSettings = Arr::get($config, 'calendar_settings.gregorian', ['timezone' => $appTimezone]);
            
            if ($gregorianCalendar instanceof ConfigurableCalendarInterface) {
                $gregorianCalendar->configure($gregorianSettings);
            }
            $manager->register($gregorianCalendar);

            // Register Islamic calendar (global lunar calendar)
            $islamicCalendar = self::resolveCalendar($container, \Lisoing\Calendar\Calendars\IslamicCalendar::class);
            $islamicSettings = Arr::get($config, 'calendar_settings.islamic', ['timezone' => 'UTC']);
            
            if ($islamicCalendar instanceof ConfigurableCalendarInterface) {
                $islamicCalendar->configure($islamicSettings);
            }
            $manager->register($islamicCalendar);

            // Auto-discover and register country classes
            $this->discoverCountries();

            // Discover calendars from Country classes
            $calendars = Arr::get($config, 'calendars', []);
            $settings = Arr::get($config, 'calendar_settings', []);

            foreach (Registry::all() as $countryCode => $countryClass) {
                $calendarClass = $countryClass::calendarClass();

                // Skip if calendarClass returns country code (no calendar defined)
                if (is_string($calendarClass) && class_exists($calendarClass)) {
                    $calendar = self::resolveCalendar($container, $calendarClass);
                    $identifier = $calendar->identifier();

                    if ($calendar instanceof ConfigurableCalendarInterface) {
                        // Use Laravel's app timezone if not configured for this calendar
                        $appTimezone = (string) $configRepository->get('app.timezone', 'UTC');
                        $calendarSettings = $settings[$identifier] ?? [];
                        
                        // Set timezone from config or use app timezone
                        if (! isset($calendarSettings['timezone'])) {
                            $calendarSettings['timezone'] = $appTimezone;
                        }
                        
                        $calendar->configure($calendarSettings);
                    }

                    $manager->register($calendar);

                    // Register country code as alias to calendar identifier
                    $manager->registerAlias($countryCode, $identifier);
                }
            }

            // Track registered calendar identifiers
            $registeredIdentifiers = ['gregorian'];
            foreach (Registry::all() as $countryCode => $countryClass) {
                $calendarClass = $countryClass::calendarClass();
                if (is_string($calendarClass) && class_exists($calendarClass)) {
                    $tempCalendar = self::resolveCalendar($container, $calendarClass);
                    $registeredIdentifiers[] = $tempCalendar->identifier();
                }
            }

            // Register additional calendars from config
            foreach ($calendars as $identifier => $calendarClass) {
                if (in_array($identifier, $registeredIdentifiers, true)) {
                    continue; // Skip if already registered
                }

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

            // Register additional aliases from config
            $aliases = Arr::get($config, 'calendar_aliases', []);
            foreach ($aliases as $alias => $target) {
                $manager->registerAlias((string) $alias, (string) $target);
            }

            return $manager;
        });

        $this->app->bind('calendar', static fn (Container $container): CalendarManager => $container->make(CalendarManager::class));

        $this->app->singleton(FormatterManager::class, function (Container $container): FormatterManager {
            $config = $container->make('config')->get('calendar', []);
            $formatters = Arr::get($config, 'formatters', []);

            // Auto-discover formatters from calendars via Country classes
            foreach (Registry::all() as $countryCode => $countryClass) {
                $calendarClass = $countryClass::calendarClass();
                if (is_string($calendarClass) && class_exists($calendarClass)) {
                    $tempCalendar = self::resolveCalendar($container, $calendarClass);
                    $identifier = $tempCalendar->identifier();
                    // Register lunar formatter for lunisolar calendars
                    if ($tempCalendar instanceof \Lisoing\Calendar\Contracts\LunisolarCalendarInterface) {
                        if (! isset($formatters[$identifier])) {
                            $formatters[$identifier] = LunarFormatter::class;
                        }
                    }
                }
            }

            return new FormatterManager(
                container: $container,
                config: [
                    'formatters' => $formatters,
                ]
            );
        });

        $this->app->bind('calendar.formatter', static fn (Container $container): FormatterManager => $container->make(FormatterManager::class));
        $this->registerHolidayManager();

        $this->registerToolkit();
    }

    public function boot(): void
    {
        // Auto-discover and load translation files for each country
        // Each country directory in resources/lang/ gets registered as its own namespace
        $this->loadCountryTranslations();
    }

    /**
     * Auto-discover and load translation files for each country.
     * Scans resources/lang/ for country directories and registers them.
     */
    private function loadCountryTranslations(): void
    {
        $translationsPath = dirname(__DIR__).'/resources/lang';

        if (! is_dir($translationsPath)) {
            return;
        }

        // Scan for country directories (e.g., cambodia/, nepal/, etc.)
        foreach (glob($translationsPath.'/*', GLOB_ONLYDIR) as $countryPath) {
            $countryName = basename($countryPath);

            // Skip if not a valid directory name
            if ($countryName === '' || $countryName === '.' || $countryName === '..') {
                continue;
            }

            // Register this country's translations as a namespace
            // e.g., resources/lang/cambodia/ -> 'cambodia' namespace
            $this->loadTranslationsFrom($countryPath, $countryName);
        }
    }

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

            // Discover providers from Country classes
            $providers = Arr::get($config, 'countries', []);
            $settings = Arr::get($config, 'holiday_settings', []);

            foreach (Registry::all() as $countryCode => $countryClass) {
                $provider = $countryClass::make()->provider();

                if ($provider instanceof ConfigurableHolidayProviderInterface) {
                    $provider->configure($settings[strtoupper($countryCode)] ?? []);
                }

                if (strtoupper($countryCode) !== strtoupper($provider->countryCode())) {
                    throw new BindingResolutionException(sprintf(
                        'Country code [%s] does not match provider [%s] identifier [%s].',
                        $countryCode,
                        $countryClass,
                        $provider->countryCode()
                    ));
                }

                $manager->register($countryCode, $provider);
            }

            // Track registered country codes
            $registeredCountryCodes = array_keys(Registry::all());

            // Register additional providers from config
            foreach ($providers as $countryCode => $providerClass) {
                if (in_array(strtoupper((string) $countryCode), $registeredCountryCodes, true)) {
                    continue; // Skip if already registered
                }

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
            $holidayManager = $container->bound(HolidayManager::class)
                ? $container->make(HolidayManager::class)
                : null;

            return new CalendarToolkit(
                calendarManager: $container->make(CalendarManager::class),
                formatterManager: $container->make(FormatterManager::class),
                holidayManager: $holidayManager
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
     * Auto-discover and register country classes from the Countries directory.
     */
    private function discoverCountries(): void
    {
        $countriesPath = __DIR__.'/Countries';

        if (! is_dir($countriesPath)) {
            return;
        }

        foreach (glob($countriesPath.'/*.php') as $filename) {
            if (basename($filename) === 'Country.php' || basename($filename) === 'Registry.php') {
                continue;
            }

            $className = 'Lisoing\\Countries\\'.basename($filename, '.php');

            if (! class_exists($className)) {
                continue;
            }

            if (! is_subclass_of($className, Country::class)) {
                continue;
            }

            // Register the country
            $className::register();
        }
    }

}
