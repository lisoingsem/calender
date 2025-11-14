<?php

declare(strict_types=1);

namespace Tests\Unit\Khmer;

use Lisoing\Calendar\Support\Khmer\NewYearAngels;
use Lisoing\Calendar\Support\Khmer\NewYearAngel;
use Lisoing\Calendar\Support\Khmer\SongkranCalculator;
use PHPUnit\Framework\TestCase;

final class NewYearAngelsTest extends TestCase
{
    public function testGetAngelForDay(): void
    {
        // Sunday (0)
        $angel = NewYearAngels::getAngelForDay(0);
        $this->assertInstanceOf(NewYearAngel::class, $angel);
        $this->assertEquals(0, $angel->dayOfWeek());
        $this->assertEquals('tungsa_tevy', $angel->nameKey());

        // Monday (1)
        $angel = NewYearAngels::getAngelForDay(1);
        $this->assertEquals(1, $angel->dayOfWeek());
        $this->assertEquals('koreak_tevy', $angel->nameKey());

        // Saturday (6)
        $angel = NewYearAngels::getAngelForDay(6);
        $this->assertEquals(6, $angel->dayOfWeek());
        $this->assertEquals('mohurea_tevy', $angel->nameKey());
    }

    public function testGetAngelForDayThrowsExceptionForInvalidDay(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NewYearAngels::getAngelForDay(7);
    }

    public function testGetAllAngels(): void
    {
        $angels = NewYearAngels::getAllAngels();
        $this->assertCount(7, $angels);

        foreach ($angels as $dayOfWeek => $angel) {
            $this->assertInstanceOf(NewYearAngel::class, $angel);
            $this->assertEquals($dayOfWeek, $angel->dayOfWeek());
        }
    }

    public function testGetAngelForYear(): void
    {
        $calculator = new SongkranCalculator();
        $angel = NewYearAngels::getAngelForYear(2025, $calculator);
        $this->assertInstanceOf(NewYearAngel::class, $angel);
        $this->assertGreaterThanOrEqual(0, $angel->dayOfWeek());
        $this->assertLessThan(7, $angel->dayOfWeek());
    }

    public function testAngelProperties(): void
    {
        $angel = NewYearAngels::getAngelForDay(0); // Sunday - Tungsa Tevy
        $this->assertEquals('tungsa_tevy', $angel->nameKey());
        $this->assertEquals('ruby_necklace', $angel->jewelryKey());
        $this->assertEquals('pomegranate_flower', $angel->flowerKey());
        $this->assertEquals('fig_fruit', $angel->foodKey());
        $this->assertEquals('disc_of_power', $angel->rightHandKey());
        $this->assertEquals('shell', $angel->leftHandKey());
        $this->assertEquals('garuda', $angel->animalKey());
    }
}

