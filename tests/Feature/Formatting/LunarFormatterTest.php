<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests\Feature\Formatting;

use Lisoing\Calendar\Formatting\FormatterManager;
use Lisoing\Calendar\Tests\TestCase;
use Lisoing\Calendar\ValueObjects\CalendarDate;

final class LunarFormatterTest extends TestCase
{
    public function test_it_formats_khmer_lunar_date_in_english(): void
    {
        /** @var FormatterManager $formatterManager */
        $formatterManager = $this->app->make(FormatterManager::class);

        $calendarDate = new CalendarDate(2025, 13, 1, 'khmer_chhankitek');

        $formatted = $formatterManager->format($calendarDate, 'en');

        $this->assertStringContainsString('Monday', $formatted);
        $this->assertStringContainsString('Waxing', $formatted);
        $this->assertStringContainsString('Leap Month', $formatted);
        $this->assertStringContainsString('2025', $formatted);
    }

    public function test_it_formats_khmer_lunar_date_in_khmer(): void
    {
        /** @var FormatterManager $formatterManager */
        $formatterManager = $this->app->make(FormatterManager::class);

        $calendarDate = new CalendarDate(2025, 13, 1, 'khmer_chhankitek');

        $formatted = $formatterManager->format($calendarDate, 'km');

        $this->assertStringContainsString('ថ្ងៃចន្ទ', $formatted);
        $this->assertStringContainsString('កើត', $formatted);
        $this->assertStringContainsString('ខែបន្ថែម', $formatted);
        $this->assertStringContainsString('២០២៥', $formatted);
    }
}
