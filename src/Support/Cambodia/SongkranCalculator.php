<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Songkran Calculator implementing historical Khmer calendar algorithms.
 *
 * Based on traditional Khmer calendar calculations for determining
 * Songkran (Khmer New Year) date, time, and duration.
 */
final class SongkranCalculator
{
    private const CHAET = 5;
    private const PISAK = 6;

    /**
     * Calculate Kromtupol (ក្រមធុបុល) value for a given Jolsakarach year.
     *
     * @param  int  $jsYear  Jolsakarach year
     * @return int Kromtupol value
     */
    public function getKromtupol(int $jsYear): int
    {
        $t = $jsYear * 292207 + 373;
        $ahk = (int) floor($t / 800) + 1;
        $mod = $t % 800;
        $krom = 800 - $mod;

        return $krom;
    }

    /**
     * Calculate Matyum (មធ្យម​ព្រះអាទិត្យ) - average sun position.
     *
     * Returns Rasey (រាសី), Angsa (អ័ង្សា), and Liba (លិប្តា).
     *
     * @param  int  $krom  Kromtupol value
     * @param  int  $sotin  Sotin value (363-366)
     * @return array{0: int, 1: int, 2: int} [Rasey, Angsa, Liba]
     */
    public function matyom(int $krom, int $sotin): array
    {
        $d1 = ($sotin * 800) + $krom;
        $rasey = (int) floor($d1 / 24350);
        $mod1 = $d1 % 24350;
        $angsa = (int) floor($mod1 / 811);
        $mod2 = $mod1 % 811;
        $liba = (int) floor($mod2 / 14) - 3;

        return [$rasey, $angsa, $liba];
    }

    /**
     * Calculate PhalLumet (ផល​លម្អិត) - detailed result for sotin 363.
     *
     * @param  array{0: int, 1: int, 2: int}  $mat  Matyum result [Rasey, Angsa, Liba]
     * @return array{0: int, 1: int, 2: int} [Rasey, Angsa, Liba]
     */
    public function phalLumet(array $mat): array
    {
        $rdif = $mat[0] - 2; // always 9 since mat[0] is 11
        $adif = $mat[1] - 20;
        $ken = [$rdif, $adif, $mat[2]];
        $kenr = $rdif;

        $phal = match ($kenr) {
            0, 1, 2 => [$kenr, 0, 0],
            3, 4, 5 => $this->subtractR([5, 29, 60], $ken),
            6, 7, 8 => $this->subtractR($ken, [6, 0, 0]),
            9, 10, 11 => $this->reduceR($this->subtractR([11, 29, 60], $ken)),
            default => throw new RuntimeException("Invalid kenr value: {$kenr}"),
        };

        $kon = ($phal[0] * 2) + 1;
        $chaya = 129;
        $t = (($phal[1] - 15) * 60 + 30) * $kon;
        $lup = (int) floor($t / 900);
        $t3 = $lup + $chaya;
        $angsa = (int) floor($t3 / 60);
        $liba = $t3 % 60;

        return [0, $angsa, $liba];
    }

    /**
     * Calculate Somphot (សំផុត​ព្រះអាទិត្យ) - final sun position.
     *
     * @param  array{0: int, 1: int, 2: int}  $mat  Matyum result
     * @param  array{0: int, 1: int, 2: int}  $phal  PhalLumet result
     * @return array{0: int, 1: int, 2: int} [Rasey, Angsa, Liba]
     */
    public function somphotSun(array $mat, array $phal): array
    {
        $sompot = $this->addR($mat, $phal);

        return $this->reduceR($sompot);
    }

    /**
     * Determine Sotin value (363, 364, 365, or 366) and Vonobot status.
     *
     * @param  int  $jsYear  Jolsakarach year
     * @param  int  $krom  Kromtupol value
     * @return array{0: int, 1: int} [Sotin value, Vonobot (0=1 day, 1=2 days)]
     */
    public function getSotin(int $jsYear, int $krom): array
    {
        $sotin = 363;
        $loop = 4;
        $somphotlist = [[0, 0, 0]];

        for ($i = 0; $i < $loop; $i++) {
            $currentSotin = $sotin + $i;
            $mat = $this->matyom($krom, $currentSotin);
            $phal = $this->phalLumet($mat);
            $somphot = $this->somphotSun($mat, $phal);
            $somphotlist[$i] = $somphot;
        }

        $dupAngsa = $this->isDupAngsa($somphotlist);
        $sotin364 = $this->isSotin364($somphotlist);

        if ($sotin364 === 1) {
            $sotin = 364;
        }

        return [$sotin, $dupAngsa];
    }

    /**
     * Check if there are duplicate Angsa values in Somphot list.
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $somphotList  List of Somphot results
     * @return int 0=no duplicate (1 day Vonobot), 1=has duplicate (2 days Vonobot)
     */
    public function isDupAngsa(array $somphotList): int
    {
        $dup = [];

        for ($i = 0; $i < 4; $i++) {
            $val = $somphotList[$i][1] ?? 0;
            $dup[$val] = ($dup[$val] ?? 0) + 1;
        }

        foreach ($dup as $v) {
            if ($v > 1) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * Check if Sotin is 364 based on Somphot pattern.
     *
     * Pattern: r11,h29,l(0-59), r0,h0,l(0-59), r0,h1..., r0,h2,...
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $somphotList  List of Somphot results
     * @return int 1 if Sotin is 364, 0 otherwise
     */
    public function isSotin364(array $somphotList): int
    {
        if (count($somphotList) < 3) {
            return 0;
        }

        if (
            ($somphotList[0][0] ?? 0) === 11 &&
            ($somphotList[0][1] ?? 0) === 29 &&
            ($somphotList[1][0] ?? 0) === 0 &&
            ($somphotList[1][1] ?? 0) === 0 &&
            ($somphotList[2][0] ?? 0) === 0 &&
            ($somphotList[2][1] ?? 0) === 1
        ) {
            return 1;
        }

        return 0;
    }

    /**
     * Calculate Songkran time from Liba value.
     *
     * @param  int  $liba  Liba value (0-59)
     * @return array{0: int, 1: int} [hour, minute]
     */
    public function songkranTime(int $liba): array
    {
        if ($liba > 59) {
            throw new RuntimeException("Liba cannot be greater than 59, got: {$liba}");
        }

        $lup = (int) floor($liba * 4 / 10);
        $rem = ($liba * 4) % 10;
        $min = (int) floor($rem * 60 / 10);
        $chour = 23;
        $cmin = 60;
        $min = $cmin - $min;
        $hour = $chour - $lup;

        return $this->reduceTime([$hour, $min]);
    }

    /**
     * Get Songkran information for a given AD year.
     *
     * @param  int  $adYear  Gregorian year
     * @return array{0: int, 1: array{0: int, 1: int}} [Vonobot (1 or 2), [hour, minute]]
     */
    public function getSongkran(int $adYear): array
    {
        $jsYear = $this->convertADtoJS($adYear);
        $krom = $this->getKromtupol($jsYear - 1);
        $sotinR = $this->getSotin($jsYear, $krom);
        $sotin = $sotinR[0];
        $vonobot = $sotinR[1] === 1 ? 2 : 1;

        // Special rule: When previous year has leap month (botleap === 1)
        // and Leungsak is Day 7, vonobot should be 2 (4 days New Year)
        $botleap = $this->getBotetheiLeap($adYear - 1);
        $leungsak = $this->getLeungsak($adYear);
        if ($botleap === 1 && $leungsak[0] === 7) {
            $vonobot = 2;
        }

        $mat = $this->matyom($krom, $sotin);
        $phal = $this->phalLumet($mat);
        $somphot = $this->somphotSun($mat, $phal);
        $liba = $somphot[2];
        $time = $this->songkranTime($liba);

        return [$vonobot, $time];
    }

    /**
     * Get Leungsak date and day of week.
     *
     * @param  int  $adYear  Gregorian year
     * @return array{0: int, 1: int, 2: int} [date (1-15), month (5 or 6), dayOfWeek (0-6)]
     */
    public function getLeungsak(int $adYear): array
    {
        $jsYear = $this->convertADtoJS($adYear);
        $beYear = $adYear + 544;
        $ahk = $this->getAharkun($beYear);
        $bot = $this->getBotethei($beYear);

        if ($bot >= 6) {
            $month = self::CHAET;

            // Check for previous year for leap month (type 1) or both (type 3)
            $botleap = $this->getBotetheiLeap($adYear - 1);
            if ($botleap === 1 || $botleap === 3) {
                $bot++;
            }
        } else {
            $month = self::PISAK;
            $bot++;
        }

        // Day of week: (aharkun - 2) % 7
        // 0=Saturday, 1=Sunday, 2=Monday, ..., 6=Friday
        $pea = ($ahk - 2) % 7;
        if ($pea < 0) {
            $pea += 7;
        }

        return [$bot, $month, $pea];
    }

    /**
     * Convert AD year to Jolsakarach (JS) year.
     *
     * @param  int  $adYear  Gregorian year
     * @return int Jolsakarach year
     */
    private function convertADtoJS(int $adYear): int
    {
        return ($adYear + 544) - 1182;
    }

    /**
     * Calculate Aharkun (ហារគុណ) for Buddhist Era year.
     *
     * @param  int  $beYear  Buddhist Era year
     * @return int Aharkun value
     */
    private function getAharkun(int $beYear): int
    {
        $t = $beYear * 292207 + 373;

        return (int) floor($t / 800) + 1;
    }

    /**
     * Calculate Botethei (បូតិថី) for Buddhist Era year.
     *
     * @param  int  $beYear  Buddhist Era year
     * @return int Botethei value
     */
    private function getBotethei(int $beYear): int
    {
        $ahk = $this->getAharkun($beYear);
        $a = 11 * $ahk + 650;

        return (int) (($ahk + floor($a / 692)) % 30);
    }

    /**
     * Get Botethei leap status for AD year.
     *
     * @param  int  $adYear  Gregorian year
     * @return int Leap status (0, 1, 2, or 3)
     */
    private function getBotetheiLeap(int $adYear): int
    {
        $beYear = $adYear + 544;
        $avoman = $this->getAvoman($beYear);
        $bodithey = $this->getBotethei($beYear);

        $leapMonth = ($bodithey >= 25) ? 1 : 0;
        $leapDay = ($avoman < 127) ? 2 : 0;

        return $leapMonth + $leapDay;
    }

    /**
     * Calculate Avoman (អវមាន) for Buddhist Era year.
     *
     * @param  int  $beYear  Buddhist Era year
     * @return int Avoman value
     */
    private function getAvoman(int $beYear): int
    {
        $ahk = $this->getAharkun($beYear);
        $a = 11 * $ahk + 650;

        return $a % 692;
    }

    /**
     * Add two Rasey values.
     *
     * @param  array{0: int, 1: int, 2: int}  $r1  [Rasey, Angsa, Liba]
     * @param  array{0: int, 1: int, 2: int}  $r2  [Rasey, Angsa, Liba]
     * @return array{0: int, 1: int, 2: int} [Rasey, Angsa, Liba]
     */
    private function addR(array $r1, array $r2): array
    {
        $liba = $r1[2] + $r2[2];
        $libaCarry = (int) floor($liba / 60);
        $liba = $liba % 60;

        $angsa = $r1[1] + $r2[1] + $libaCarry;
        $angsaCarry = (int) floor($angsa / 60);
        $angsa = $angsa % 60;

        $rasey = $r1[0] + $r2[0] + $angsaCarry;
        $rasey = $rasey % 12;

        return [$rasey, $angsa, $liba];
    }

    /**
     * Subtract Rasey values (r2 from r1).
     *
     * @param  array{0: int, 1: int, 2: int}  $r1  [Rasey, Angsa, Liba]
     * @param  array{0: int, 1: int, 2: int}  $r2  [Rasey, Angsa, Liba]
     * @return array{0: int, 1: int, 2: int} [Rasey, Angsa, Liba]
     */
    private function subtractR(array $r1, array $r2): array
    {
        $liba = $r1[2] - $r2[2];
        if ($liba < 0) {
            $liba += 60;
            $r1[1]--;
        }

        $angsa = $r1[1] - $r2[1];
        if ($angsa < 0) {
            $angsa += 60;
            $r1[0]--;
        }

        $rasey = $r1[0] - $r2[0];
        if ($rasey < 0) {
            $rasey += 12;
        }

        return [$rasey, $angsa, $liba];
    }

    /**
     * Reduce Rasey values to valid ranges.
     *
     * @param  array{0: int, 1: int, 2: int}  $r  [Rasey, Angsa, Liba]
     * @return array{0: int, 1: int, 2: int} [Rasey, Angsa, Liba]
     */
    private function reduceR(array $r): array
    {
        $liba = $r[2];
        $libaCarry = (int) floor($liba / 60);
        $liba = $liba % 60;
        if ($liba < 0) {
            $liba += 60;
            $libaCarry--;
        }

        $angsa = $r[1] + $libaCarry;
        $angsaCarry = (int) floor($angsa / 60);
        $angsa = $angsa % 60;
        if ($angsa < 0) {
            $angsa += 60;
            $angsaCarry--;
        }

        $rasey = ($r[0] + $angsaCarry) % 12;
        if ($rasey < 0) {
            $rasey += 12;
        }

        return [$rasey, $angsa, $liba];
    }

    /**
     * Reduce time values to valid ranges (0-23 hours, 0-59 minutes).
     *
     * @param  array{0: int, 1: int}  $time  [hour, minute]
     * @return array{0: int, 1: int} [hour, minute]
     */
    private function reduceTime(array $time): array
    {
        $hour = $time[0];
        $minute = $time[1];

        if ($minute < 0) {
            $minute += 60;
            $hour--;
        } elseif ($minute >= 60) {
            $hour += (int) floor($minute / 60);
            $minute = $minute % 60;
        }

        if ($hour < 0) {
            $hour += 24;
        } elseif ($hour >= 24) {
            $hour = $hour % 24;
        }

        return [$hour, $minute];
    }
}

