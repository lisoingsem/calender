<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Formatting;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Lisoing\Calendar\CalendarManager;
use Lisoing\Calendar\Support\LocaleResolver;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class LunarFormatter implements FormatterInterface
{
    public function __construct(
        private readonly CalendarManager $calendarManager
    ) {}

    public function format(CalendarDate $date, ?string $locale = null): string
    {
        $locale = $this->resolveLocale($locale);

        $dateTime = $this->calendarManager->toDateTime($date);

        $dayOfWeekKey = strtolower($dateTime->englishDayOfWeek);
        $dayOfWeek = $this->translate("day_of_week.{$dayOfWeekKey}", $locale, ucfirst($dayOfWeekKey));

        $phaseKey = (string) $date->getContextValue('phase', $date->getDay() <= 15 ? 'keit' : 'roaj');
        $phase = $this->translate("phase.{$phaseKey}", $locale, $phaseKey);

        $monthKey = (string) $date->getMonth();
        $month = $this->translate("month.{$monthKey}", $locale, $monthKey);

        $lunarDay = $this->translate(
            'lunar_day.format',
            $locale,
            sprintf('%d %s', $date->getDay(), $phase),
            [
                'day' => $this->formatNumber($date->getDay(), $locale),
                'phase' => $phase,
            ]
        );

        $year = $this->formatNumber($date->getYear(), $locale);

        return $this->translate(
            'templates.full',
            $locale,
            sprintf('%s %s %s %s', $dayOfWeek, $lunarDay, $month, $year),
            [
                'day_of_week' => $dayOfWeek,
                'lunar_day' => $lunarDay,
                'month' => $month,
                'year' => $year,
            ]
        );
    }

    private function resolveLocale(?string $locale): string
    {
        $supported = Config::get('calendar.supported_locales', []);

        if (! is_array($supported)) {
            $supported = [];
        }

        $supported = array_values(array_unique(array_filter(array_map(
            static fn ($value): string => LocaleResolver::canonicalize(is_string($value) ? $value : ''),
            $supported
        ), static fn (string $value): bool => $value !== '')));

        $default = LocaleResolver::canonicalize((string) Config::get('calendar.fallback_locale')) ?: 'en';
        $appLocale = LocaleResolver::canonicalize((string) App::getLocale());
        $appFallback = LocaleResolver::canonicalize((string) Config::get('app.fallback_locale'));

        return LocaleResolver::resolve($locale, $supported, $default, $appLocale, $appFallback);
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function translate(string $key, string $locale, string $fallback, array $replace = []): string
    {
        $translation = Lang::get("calendar::calendar.phrases.{$key}", $replace, $locale);

        if (is_string($translation) && $translation !== "calendar::calendar.phrases.{$key}") {
            return $translation;
        }

        return $fallback;
    }

    private function formatNumber(int $number, string $locale): string
    {
        $numerals = Lang::get('calendar::calendar.numerals', [], $locale);

        if (! is_array($numerals) || $numerals === []) {
            return (string) $number;
        }

        $characters = str_split((string) $number);

        return implode('', array_map(
            static fn (string $char): string => $numerals[$char] ?? $char,
            $characters
        ));
    }
}
