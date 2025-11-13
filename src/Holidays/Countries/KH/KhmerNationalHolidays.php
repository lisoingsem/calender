<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Holidays\Countries\KH;

use Lisoing\Calendar\Holidays\AbstractHolidayProvider;

final class KhmerNationalHolidays extends AbstractHolidayProvider
{
    public function countryCode(): string
    {
        return 'KH';
    }

    public function name(): string
    {
        return 'Cambodia';
    }

    protected function definitions(int $year): array
    {
        return [
            [
                'id' => sprintf('khmer_new_year_%d', $year),
                'name_key' => 'calendar::holidays.kh.khmer_new_year',
                'date' => sprintf('%d-04-14', $year),
                'type' => 'public',
            ],
            [
                'id' => sprintf('visak_bochea_%d', $year),
                'name_key' => 'calendar::holidays.kh.visak_bochea',
                'date' => sprintf('%d-05-22', $year),
                'type' => 'public',
            ],
        ];
    }
}

