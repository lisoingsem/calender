<?php

declare(strict_types=1);

namespace Lisoing\Calendar;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Lisoing\Calendar\Calendars\GregorianCalendar;
use Lisoing\Calendar\Calendars\KhmerLunarCalendar;
use Lisoing\Calendar\Holidays\Countries\KH\KhmerNationalHolidays;
use Lisoing\Calendar\Holidays\HolidayManager;

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

            $manager->register(new GregorianCalendar);
            $manager->register(new KhmerLunarCalendar);

            return $manager;
        });

        $this->app->bind('calendar', static fn (Container $container): CalendarManager => $container->make(CalendarManager::class));

        $this->app->singleton(HolidayManager::class, static function (Container $container): HolidayManager {
            $config = $container->make('config')->get('calendar');

            $manager = new HolidayManager($config['fallback_locale']);

            $manager->register(new KhmerNationalHolidays);

            return $manager;
        });
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
}
