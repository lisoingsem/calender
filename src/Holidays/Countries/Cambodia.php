<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Holidays\Countries;

use Illuminate\Support\Facades\App;
use Lisoing\Calendar\CalendarManager;
use Lisoing\Calendar\Holidays\AbstractHolidayProvider;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class Cambodia extends AbstractHolidayProvider
{
    private const KHMER_CALENDAR = 'khmer_chhankitek';

    public function countryCode(): string
    {
        return 'KH';
    }

    public function name(): string
    {
        return 'Cambodia';
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function definitions(int $year): array
    {
        $holidays = [
            $this->solar(
                slug: 'international_new_year',
                year: $year,
                default: sprintf('%d-01-01', $year)
            ),
            $this->solar(
                slug: 'victory_over_genocide_regime',
                year: $year,
                default: sprintf('%d-01-07', $year)
            ),
            $this->solar(
                slug: 'international_womens_day',
                year: $year,
                default: sprintf('%d-03-08', $year)
            ),
            $this->solar(
                slug: 'king_fathers_memorial',
                year: $year,
                default: sprintf('%d-10-15', $year)
            ),
        ];

        $khmerNewYearDate = $this->calendar()->toDateTime(new CalendarDate(
            year: $year,
            month: 13,
            day: 1,
            calendar: self::KHMER_CALENDAR
        ));

        $holidays[] = $this->lunar(
            slug: 'khmer_new_year',
            year: $year,
            defaultDate: $khmerNewYearDate->toDateString()
        );

        $visakDate = $this->calendar()->toDateTime(new CalendarDate(
            year: $year,
            month: 8,
            day: 15,
            calendar: self::KHMER_CALENDAR
        ));

        $holidays[] = $this->lunar(
            slug: 'visak_bochea',
            year: $year,
            defaultDate: $visakDate->toDateString()
        );

        return $holidays;
    }

    private function solar(string $slug, int $year, string $default, string $type = 'public'): array
    {
        $date = $this->resolveDateOverride($slug, $year, $default);

        return [
            'id' => sprintf('%s_%d', $slug, $year),
            'slug' => $slug,
            'date' => $date,
            'type' => $type,
        ];
    }

    private function lunar(string $slug, int $year, string $defaultDate, string $type = 'public'): array
    {
        $date = $this->resolveDateOverride($slug, $year, $defaultDate);

        return [
            'id' => sprintf('%s_%d', $slug, $year),
            'slug' => $slug,
            'date' => $date,
            'type' => $type,
        ];
    }

    private function calendar(): CalendarManager
    {
        /** @var CalendarManager $manager */
        $manager = App::make(CalendarManager::class);

        return $manager;
    }

    protected function countryDirectory(): string
    {
        return 'cambodia';
    }
}
