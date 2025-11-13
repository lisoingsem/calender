<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Formatting;

use Lisoing\Calendar\ValueObjects\CalendarDate;

interface FormatterInterface
{
    public function format(CalendarDate $date, ?string $locale = null): string;
}
