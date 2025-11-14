<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Lisoing\Calendar\ValueObjects\CalendarDate;

final class CalendarDateFormatter
{
    public function __construct(
        private readonly CalendarDate $date,
        private readonly string $locale
    ) {
    }

    /**
     * Format the date using Carbon-style format string.
     *
     * @param  string  $format  Format string (e.g., 'dddd D MMMM YYYY')
     * @return string Formatted date string
     */
    public function format(string $format): string
    {
        $result = '';
        $length = strlen($format);
        $i = 0;

        while ($i < $length) {
            // Check for escaped characters
            if ($format[$i] === '[') {
                $end = strpos($format, ']', $i + 1);
                if ($end !== false) {
                    $result .= substr($format, $i + 1, $end - $i - 1);
                    $i = $end + 1;
                    continue;
                }
            }

            // Try to match format tokens
            $matched = false;
            $remaining = substr($format, $i);

            // Check for multi-character tokens first (longest match)
            $tokens = [
                'dddd' => fn () => $this->formatDayOfWeek('full'),
                'ddd' => fn () => $this->formatDayOfWeek('short'),
                'dd' => fn () => $this->formatDayOfWeek('min'),
                'DDDD' => fn () => $this->formatDayOfYear(true),
                'DDDo' => fn () => $this->formatDayOfYear(false, true),
                'DDD' => fn () => $this->formatDayOfYear(),
                'DD' => fn () => $this->formatDay(true), // Zero-padded day
                'MMMM' => fn () => $this->formatMonth('full'),
                'MMM' => fn () => $this->formatMonth('short'),
                'MM' => fn () => $this->formatMonth('numeric', true),
                'Mo' => fn () => $this->formatMonth('numeric', false, true),
                'YYYY' => fn () => $this->formatYear(4),
                'YYY' => fn () => $this->formatYear(3),
                'YY' => fn () => $this->formatYear(2),
                'OY' => fn () => $this->formatYearAlternative(),
                'OM' => fn () => $this->formatMonthAlternative(),
                'OD' => fn () => $this->formatDayAlternative(),
                'LL' => fn () => $this->formatLunarDay(),
                'LLLL' => fn () => $this->formatLunarDayFull(),
            ];

            foreach ($tokens as $token => $formatter) {
                if (str_starts_with($remaining, $token)) {
                    $result .= $formatter();
                    $i += strlen($token);
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                continue;
            }

            // Single character tokens
            $char = $format[$i];
            $result .= match ($char) {
                'D' => $this->formatDay(),
                'd' => (string) $this->date->getContextValue('weekday_index', 0),
                'M' => $this->formatMonth('numeric'),
                'Y' => $this->formatYear(4),
                'L' => $this->formatLunarDay(),
                'A' => $this->formatAnimalYear(),
                'E' => $this->formatEraYear(),
                'B' => $this->formatBuddhistEraYear(),
                'P' => $this->formatPhase(),
                default => $char,
            };

            $i++;
        }

        return $result;
    }

    private function formatDay(bool $padded = false): string
    {
        $day = $this->date->getDay();
        
        if ($padded) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }
        
        return (string) $day;
    }

    private function formatDayAlternative(): string
    {
        return $this->formatAlternativeNumber($this->date->getDay());
    }

    private function formatDayOfWeek(string $format = 'full'): string
    {
        $dayOfWeek = $this->date->getDayOfWeek($this->locale);

        if ($dayOfWeek === '') {
            return '';
        }

        if ($format === 'min') {
            // Return first 2 characters
            return mb_substr($dayOfWeek, 0, 2);
        }

        if ($format === 'short') {
            // Return first 3 characters
            return mb_substr($dayOfWeek, 0, 3);
        }

        return $dayOfWeek;
    }

    private function formatMonth(string $format = 'numeric', bool $padded = false, bool $ordinal = false): string
    {
        if ($format === 'numeric') {
            $month = $this->date->getMonth();
            if ($padded) {
                return str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            }

            if ($ordinal) {
                return $month . $this->getOrdinalSuffix($month);
            }

            return (string) $month;
        }

        $monthName = $this->date->getLunarMonth($this->locale);

        if ($format === 'short') {
            return mb_substr($monthName, 0, 3);
        }

        return $monthName;
    }

    private function formatMonthAlternative(): string
    {
        return $this->formatAlternativeNumber($this->date->getMonth());
    }

    private function formatYear(int $digits = 4): string
    {
        $year = $this->date->getYear();

        if ($digits === 2) {
            return substr((string) $year, -2);
        }

        if ($digits === 3) {
            return str_pad((string) $year, 3, '0', STR_PAD_LEFT);
        }

        return str_pad((string) $year, 4, '0', STR_PAD_LEFT);
    }

    private function formatYearAlternative(): string
    {
        return $this->formatAlternativeNumber($this->date->getYear());
    }

    private function formatLunarDay(): string
    {
        return $this->date->getLunarDay($this->locale);
    }

    private function formatLunarDayFull(): string
    {
        $day = $this->date->getLunarDay($this->locale);
        $month = $this->date->getLunarMonth($this->locale);

        return "{$day} {$month}";
    }

    private function formatAnimalYear(): string
    {
        return $this->date->getAnimalYear($this->locale);
    }

    private function formatEraYear(): string
    {
        return $this->date->getEraYear($this->locale);
    }

    private function formatBuddhistEraYear(): string
    {
        return $this->date->getLunarYear($this->locale);
    }

    private function formatPhase(): string
    {
        return $this->date->getPhase($this->locale);
    }

    private function formatDayOfYear(bool $padded = false, bool $ordinal = false): string
    {
        // Day of year calculation would need the original Carbon date
        // For now, return day of month as approximation
        $day = $this->date->getDay();

        if ($padded) {
            $day = str_pad((string) $day, 3, '0', STR_PAD_LEFT);
        }

        if ($ordinal) {
            return $day . $this->getOrdinalSuffix((int) $day);
        }

        return (string) $day;
    }

    private function formatAlternativeNumber(int $number): string
    {
        // Use calendar-specific formatter if available
        if ($this->date->getCalendar() === 'km') {
            return \Lisoing\Calendar\Support\Cambodia\CambodiaDateFormatter::formatAlternativeNumber($number, $this->locale);
        }

        // Default: return regular number
        return (string) $number;
    }

    private function getOrdinalSuffix(int $number): string
    {
        $suffixes = [
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
        ];

        $lastDigit = $number % 10;
        $lastTwo = $number % 100;

        if ($lastTwo >= 11 && $lastTwo <= 13) {
            return 'th';
        }

        return $suffixes[$lastDigit] ?? 'th';
    }
}

