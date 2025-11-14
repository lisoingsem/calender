<?php

declare(strict_types=1);

namespace Tests\Unit\Khmer;

use Lisoing\Calendar\Support\Khmer\SongkranCalculator;
use PHPUnit\Framework\TestCase;

final class SongkranCalculatorTest extends TestCase
{
    private SongkranCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SongkranCalculator();
    }

    public function testGetKromtupol(): void
    {
        $krom = $this->calculator->getKromtupol(843);
        $this->assertGreaterThan(0, $krom);
        $this->assertLessThanOrEqual(800, $krom);
    }

    public function testMatyom(): void
    {
        $result = $this->calculator->matyom(593, 363);
        $this->assertCount(3, $result);
        $this->assertIsInt($result[0]); // Rasey
        $this->assertIsInt($result[1]); // Angsa
        $this->assertIsInt($result[2]); // Liba
        $this->assertGreaterThanOrEqual(0, $result[0]);
        $this->assertLessThan(12, $result[0]);
    }

    public function testPhalLumet(): void
    {
        $mat = [11, 20, 0];
        $result = $this->calculator->phalLumet($mat);
        $this->assertCount(3, $result);
        $this->assertEquals(0, $result[0]); // Rasey should be 0
        $this->assertIsInt($result[1]); // Angsa
        $this->assertIsInt($result[2]); // Liba
    }

    public function testSomphotSun(): void
    {
        $mat = [11, 20, 0];
        $phal = $this->calculator->phalLumet($mat);
        $result = $this->calculator->somphotSun($mat, $phal);
        $this->assertCount(3, $result);
        $this->assertGreaterThanOrEqual(0, $result[0]);
        $this->assertLessThan(12, $result[0]);
    }

    public function testGetSotin(): void
    {
        $result = $this->calculator->getSotin(843, 593);
        $this->assertCount(2, $result);
        $sotin = $result[0];
        $vonobot = $result[1];
        $this->assertContains($sotin, [363, 364, 365, 366]);
        $this->assertContains($vonobot, [0, 1]);
    }

    public function testSongkranTime(): void
    {
        $result = $this->calculator->songkranTime(0);
        $this->assertCount(2, $result);
        $this->assertGreaterThanOrEqual(0, $result[0]);
        $this->assertLessThan(24, $result[0]);
        $this->assertGreaterThanOrEqual(0, $result[1]);
        $this->assertLessThan(60, $result[1]);
    }

    public function testGetSongkran(): void
    {
        $result = $this->calculator->getSongkran(2025);
        $this->assertCount(2, $result);
        $vonobot = $result[0];
        $time = $result[1];
        $this->assertContains($vonobot, [1, 2]);
        $this->assertCount(2, $time);
        $this->assertGreaterThanOrEqual(0, $time[0]);
        $this->assertLessThan(24, $time[0]);
    }

    public function testGetLeungsak(): void
    {
        $result = $this->calculator->getLeungsak(2025);
        $this->assertCount(3, $result);
        $day = $result[0];
        $month = $result[1];
        $dayOfWeek = $result[2];
        // Leungsak day is a lunar day (1-30, but typically 1-15 for Chaet/Pisak)
        $this->assertGreaterThanOrEqual(1, $day);
        $this->assertLessThanOrEqual(30, $day);
        $this->assertContains($month, [5, 6]);
        $this->assertGreaterThanOrEqual(0, $dayOfWeek);
        $this->assertLessThan(7, $dayOfWeek);
    }
}

