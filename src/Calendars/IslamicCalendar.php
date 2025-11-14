<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Calendars;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use Lisoing\Calendar\ValueObjects\CalendarDate;

/**
 * Islamic (Hijri) Calendar implementation.
 *
 * A purely lunar calendar that follows only moon phases (~354 days/year).
 * Does not align with solar years (no leap months).
 *
 * Uses PHP's IntlDateFormatter with 'islamic-civil' calendar.
 */
final class IslamicCalendar extends AbstractLunarCalendar
{
    private ?IntlDateFormatter $formatter = null;

    public function __construct(string $defaultTimezone = 'UTC')
    {
        parent::__construct($defaultTimezone);
    }

    public function identifier(): string
    {
        return 'islamic';
    }

    protected function convertToSolar(CalendarDate $date): CarbonImmutable
    {
        $formatter = $this->getFormatter();
        
        // Format: YYYY-MM-DD in Islamic calendar
        $islamicDate = sprintf(
            '%04d-%02d-%02d',
            $date->getYear(),
            $date->getMonth(),
            $date->getDay()
        );

        // Parse Islamic date to get timestamp
        $timestamp = (int) $formatter->parse($islamicDate);

        return (new CarbonImmutable)
            ->setTimestamp($timestamp)
            ->setTimezone(new DateTimeZone($this->timezone));
    }

    protected function convertFromSolar(CarbonImmutable $dateTime): CalendarDate
    {
        $formatter = $this->getFormatter();
        
        // Format the gregorian date to Islamic calendar
        $formatter->setPattern('yyyy-MM-dd');
        $islamicDateString = $formatter->format($dateTime->getTimestamp());

        // Parse the Islamic date string (YYYY-MM-DD)
        [$year, $month, $day] = explode('-', $islamicDateString);

        return new CalendarDate(
            year: (int) $year,
            month: (int) $month,
            day: (int) $day,
            calendar: $this->identifier(),
            context: []
        );
    }

    /**
     * Get the IntlDateFormatter instance for Islamic calendar.
     */
    private function getFormatter(): IntlDateFormatter
    {
        if ($this->formatter === null) {
            $this->formatter = new IntlDateFormatter(
                'en_US@calendar=islamic-civil',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                $this->timezone,
                IntlDateFormatter::TRADITIONAL,
                'yyyy-MM-dd'
            );
        }

        return $this->formatter;
    }

    public function configure(array $settings): void
    {
        parent::configure($settings);
        
        // Reset formatter if timezone changed
        if (isset($settings['timezone']) && is_string($settings['timezone'])) {
            $this->formatter = null;
        }
    }
}

