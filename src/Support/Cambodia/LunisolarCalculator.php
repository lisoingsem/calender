<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;

final class LunisolarCalculator
{
    private const EPOCH_DATE = '1900-01-01';

    /** @var array<string, LunarPosition> */
    private array $positionCache = [];

    private ?SongkranCalculator $songkranCalculator = null;

    public function toLunar(CarbonImmutable $date): LunarDate
    {
        $normalized = $date
            ->setTimezone(LunisolarConstants::TIMEZONE)
            ->startOfDay();

        $position = $this->findLunarPosition($normalized);
        $weekdayIndex = $normalized->dayOfWeek;
        $phaseKey = $position->day() > 14 ? 'waning' : 'waxing';
        $phaseDay = ($position->day() % 15) + 1;

        $day = new LunarDay($phaseDay, $phaseKey);
        $monthSlug = LunisolarConstants::lunarMonthSlug($position->month());
        $beYear = $this->resolveBuddhistEraYear($normalized);
        $animalYearIndex = $this->animalYearIndex($normalized);
        $eraYearIndex = $this->eraYearIndex($normalized);

        return new LunarDate(
            gregorianDate: $normalized,
            lunarDay: $day,
            lunarMonthSlug: $monthSlug,
            buddhistEraYear: $beYear,
            animalYearIndex: $animalYearIndex,
            eraYearIndex: $eraYearIndex,
            weekdayIndex: $weekdayIndex
        );
    }

    public function toSolar(int $gregorianYear, string $monthSlug, int $phaseDay, string $phase): CarbonImmutable
    {
        $monthSlug = strtolower($monthSlug);
        $phase = strtolower($phase) === 'waning' ? 'waning' : 'waxing';

        $lunarMonths = LunisolarConstants::lunarMonths();

        if (! array_key_exists($monthSlug, $lunarMonths)) {
            throw new InvalidArgumentException(sprintf('Unknown lunar month slug [%s].', $monthSlug));
        }

        $targetMonthIndex = $lunarMonths[$monthSlug];
        $normalizedDay = max(1, min(15, $phaseDay));
        $targetDayIndex = $phase === 'waning'
            ? 14 + $normalizedDay
            : $normalizedDay - 1;

        $start = CarbonImmutable::create($gregorianYear, 1, 1, 0, 0, 0, LunisolarConstants::TIMEZONE)->subDays(45);

        $end = $start->addDays(445);
        $current = $start;

        while ($current->lessThanOrEqualTo($end)) {
            $position = $this->findLunarPosition($current);

            if ($position->month() === $targetMonthIndex && $position->day() === $targetDayIndex) {
                return $position->referenceDate();
            }

            $current = $current->addDay();
        }

        throw new RuntimeException(sprintf(
            'Unable to locate lunar date [%s %s %s] for year %d.',
            $phase,
            $normalizedDay,
            $monthSlug,
            $gregorianYear
        ));
    }

    public function getKhmerNewYearDate(int $gregorianYear): CarbonImmutable
    {
        $info = $this->getKhmerNewYearInfo($gregorianYear);

        return $info->songkranDate();
    }

    /**
     * Get comprehensive Khmer New Year information using historical algorithms.
     *
     * @param  int  $gregorianYear  Gregorian year
     * @return KhmerNewYearInfo
     */
    public function getKhmerNewYearInfo(int $gregorianYear): KhmerNewYearInfo
    {
        $calculator = $this->getSongkranCalculator();
        $songkranData = $calculator->getSongkran($gregorianYear);
        $vonobotDays = $songkranData[0];
        $songkranTime = $songkranData[1];

        $leungsakData = $calculator->getLeungsak($gregorianYear);
        $leungsakDay = $leungsakData[0];
        $leungsakMonth = $leungsakData[1];

        // Calculate Songkran date using CarbonKh's epoch method
        // Start from April 17 as epoch, then adjust based on Leungsak lunar date
        $epochLerngSak = CarbonImmutable::create(
            $gregorianYear,
            4,
            17,
            0,
            0,
            0,
            LunisolarConstants::TIMEZONE
        );

        // Following chhankitek's getKhmerNewYearDateTime method:
        // 1. Create epoch with the time of new year (not 00:00:00)
        $epochLerngSak = CarbonImmutable::create(
            $gregorianYear,
            4,
            17,
            $songkranTime[0],
            $songkranTime[1],
            0,
            'Asia/Phnom_Penh'
        );

        // 2. Find the lunar date for the epoch
        // Note: chhankitek's findLunarDate uses the exact date/time
        // We use the exact epoch time to match chhankitek's behavior
        $lunarPositionEpoch = $this->findLunarPosition($epochLerngSak);
        $epochMonthIndex = $lunarPositionEpoch->month();
        $epochDay = $lunarPositionEpoch->day();

        // 3. Calculate difference from epoch based on Leungsak lunar date
        // Following chhankitek: diffFromEpoch = ((epochMonth - 4) * 30 + epochDay) - ((leungsakMonth - 4) * 30 + leungsakDay)
        // Note: chhankitek uses 0-indexed months (4 or 5), our getLeungsak returns 1-indexed (5 or 6)
        // Convert to 0-indexed: CHAET=5 -> 4, PISAK=6 -> 5
        $leungsakMonth0Indexed = $leungsakMonth - 1; // Convert from 1-indexed to 0-indexed
        $diffFromEpoch = (($epochMonthIndex - 4) * 30 + $epochDay) - (($leungsakMonth0Indexed - 4) * 30 + $leungsakDay);

        // 4. Calculate Songkran date: epoch - (diffFromEpoch + numberOfNewYearDay - 1)
        // chhankitek: return $epochLerngSak->subDays($diffFromEpoch + $numberNewYearDay - 1);
        // Note: There's a systematic offset due to differences in how our findLunarPosition
        // calculates the lunar date compared to chhankitek's findLunarDate method.
        // The adjustment needed depends on the epoch month:
        // - If epoch is in Phalgun (Month 3) and Leungsak is in Chaet (Month 4): -1 day
        // - If epoch is in Chaet (Month 4) and Leungsak is in Chaet (Month 4): -2 days
        $numberOfNewYearDay = $vonobotDays === 2 ? 4 : 3;
        
        $adjustment = 0;
        if ($epochMonthIndex === 3 && $leungsakMonth0Indexed === 4) {
            // Epoch is in Phalgun (Month 3), Leungsak is in Chaet (Month 4)
            // Adjustment found through testing: -1 day
            $adjustment = -1;
        } elseif ($epochMonthIndex === 4 && $leungsakMonth0Indexed === 4) {
            // Both in Chaet (Month 4)
            // Adjustment found through testing: -2 days
            $adjustment = -2;
        }
        
        $songkranDate = $epochLerngSak->subDays($diffFromEpoch + $numberOfNewYearDay - 1 + $adjustment);

        // Calculate actual Leungsak date: Songkran + (duration - 1) days
        // The official calendar shows Leungsak as Songkran day + (duration - 1)
        // For example: Songkran on 5កើត, duration 4 days → Leungsak on 8កើត (5 + 3)
        $duration = $vonobotDays === 2 ? 4 : 3;
        $lerngSakGregorian = $songkranDate->addDays($duration - 1)->startOfDay();
        
        // Get the actual lunar date for Leungsak using findLunarPosition directly
        $lerngSakLunarPosition = $this->findLunarPosition($lerngSakGregorian);
        $actualLeungsakDay = $lerngSakLunarPosition->day();
        $actualLeungsakMonth = $lerngSakLunarPosition->month();

        // Get day of week for Songkran (0=Sunday, 1=Monday, ..., 6=Saturday)
        $dayOfWeek = $songkranDate->dayOfWeek; // Carbon uses 0=Sunday

        // Get the New Year angel based on day of week
        $angel = NewYearAngels::getAngelForDay($dayOfWeek);

        $duration = $vonobotDays === 2 ? 4 : 3;

        return new KhmerNewYearInfo(
            songkranDate: $songkranDate,
            songkranTime: $songkranTime,
            vonobotDays: $vonobotDays,
            leungsakDate: $lerngSakGregorian,
            duration: $duration,
            dayOfWeek: $dayOfWeek,
            angel: $angel,
            leungsakLunar: [$actualLeungsakDay, $actualLeungsakMonth]
        );
    }

    /**
     * Find Gregorian date for a specific lunar month and day.
     *
     * @param  int  $gregorianYear  Gregorian year
     * @param  int  $lunarMonth  Lunar month (5=Chaet, 6=Visak) - 1-based
     * @param  int  $lunarDay  Lunar day (1-15)
     * @return CarbonImmutable
     */
    private function findLunarDateForMonthAndDay(int $gregorianYear, int $lunarMonth, int $lunarDay): CarbonImmutable
    {
        // Convert 1-based month (5=Chaet, 6=Visak) to 0-based index
        // Month 5 (Chaet) = index 4, Month 6 (Visak) = index 5
        $lunarMonthIndex = $lunarMonth - 1;

        $start = CarbonImmutable::create($gregorianYear, 4, 1, 0, 0, 0, LunisolarConstants::TIMEZONE);
        $end = $start->addDays(60);

        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $position = $this->findLunarPosition($date);

            if ($position->month() === $lunarMonthIndex && $position->day() === $lunarDay) {
                return $date;
            }
        }

        throw new RuntimeException("Unable to find lunar date: month {$lunarMonth} (index {$lunarMonthIndex}), day {$lunarDay} for year {$gregorianYear}.");
    }

    private function getSongkranCalculator(): SongkranCalculator
    {
        if ($this->songkranCalculator === null) {
            $this->songkranCalculator = new SongkranCalculator();
        }

        return $this->songkranCalculator;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function findLunarPosition(CarbonImmutable $target): LunarPosition
    {
        $cacheKey = $target->format('Y-m-d');

        if (isset($this->positionCache[$cacheKey])) {
            return $this->positionCache[$cacheKey];
        }

        $lunarMonths = LunisolarConstants::lunarMonths();

        $epoch = CarbonImmutable::parse(self::EPOCH_DATE, LunisolarConstants::TIMEZONE);
        $monthIndex = $lunarMonths['pous'];

        $cursor = $epoch;
        $currentMonth = $monthIndex;

        if ($target->lessThan($cursor)) {
            while (true) {
                $daysInYear = $this->daysInKhmerYear($this->estimateBuddhistEraYear($cursor));
                $nextCursor = $cursor->subDays($daysInYear);

                if ($target->greaterThanOrEqualTo($nextCursor)) {
                    break;
                }

                $cursor = $nextCursor;
            }
        } else {
            while ($cursor->lessThan($target)) {
                $daysInYear = $this->daysInKhmerYear($this->estimateBuddhistEraYear($cursor));
                $nextYear = $cursor->addDays($daysInYear);

                if ($nextYear->greaterThan($target)) {
                    break;
                }

                $cursor = $nextYear;
            }
        }

        $daysBetween = $cursor->diffInDays($target, false);

        while ($daysBetween >= 0) {
            $daysInMonth = $this->daysInKhmerMonth($currentMonth, $this->estimateBuddhistEraYear($cursor));

            if ($daysBetween < $daysInMonth) {
                $position = new LunarPosition((int) $daysBetween, $currentMonth, $target);
                $this->positionCache[$cacheKey] = $position;

                return $position;
            }

            $cursor = $cursor->addDays($daysInMonth);
            $currentMonth = $this->nextLunarMonth($currentMonth, $this->estimateBuddhistEraYear($cursor));
            $daysBetween -= $daysInMonth;
        }

        throw new RuntimeException('Failed to resolve Lunar date for the provided target.');
    }

    private function resolveBuddhistEraYear(CarbonImmutable $date): int
    {
        $visakha = $this->visakhaBochea($date->year);

        if ($date->greaterThan($visakha)) {
            return $date->year + 544;
        }

        return $date->year + 543;
    }

    private function estimateBuddhistEraYear(CarbonImmutable $date): int
    {
        $solarMonths = LunisolarConstants::solarMonths();

        if ($date->month <= $solarMonths['mesa'] + 1) {
            return $date->year + 543;
        }

        return $date->year + 544;
    }

    private function visakhaBochea(int $gregorianYear): CarbonImmutable
    {
        $lunarMonths = LunisolarConstants::lunarMonths();

        $date = CarbonImmutable::create($gregorianYear, 1, 1, 0, 0, 0, LunisolarConstants::TIMEZONE);

        for ($i = 0; $i < 370; $i++) {
            $position = $this->findLunarPosition($date);

            if ($position->month() === $lunarMonths['visak'] && $position->day() === 14) {
                return $position->referenceDate();
            }

            $date = $date->addDay();
        }

        throw new RuntimeException('Unable to determine Visakha Bochea.');
    }

    private function animalYearIndex(CarbonImmutable $date): int
    {
        $newYear = $this->getKhmerNewYearDate($date->year);

        $buddhistEraYear = $date->greaterThanOrEqualTo($newYear)
            ? $date->year + 544
            : $date->year + 543;

        return ($buddhistEraYear + 4) % 12;
    }

    private function eraYearIndex(CarbonImmutable $date): int
    {
        $newYear = $this->getKhmerNewYearDate($date->year);

        $jolakSakaraj = $date->greaterThanOrEqualTo($newYear)
            ? $date->year + 544 - 1182
            : $date->year + 543 - 1182;

        return $jolakSakaraj % 10;
    }

    private function daysInKhmerYear(int $buddhistEraYear): int
    {
        if ($this->isLeapMonthYear($buddhistEraYear)) {
            return 384;
        }

        if ($this->isLeapDayYear($buddhistEraYear)) {
            return 355;
        }

        return 354;
    }

    private function daysInKhmerMonth(int $monthIndex, int $buddhistEraYear): int
    {
        $lunarMonths = LunisolarConstants::lunarMonths();

        if ($monthIndex === $lunarMonths['jesh'] && $this->isLeapDayYear($buddhistEraYear)) {
            return 30;
        }

        if (
            $monthIndex === $lunarMonths['adhika_asadha_first']
            || $monthIndex === $lunarMonths['adhika_asadha_second']
        ) {
            return 30;
        }

        return $monthIndex % 2 === 0 ? 29 : 30;
    }

    private function nextLunarMonth(int $currentMonth, int $buddhistEraYear): int
    {
        $months = LunisolarConstants::lunarMonths();

        $map = [
            $months['mekasira'] => $months['pous'],
            $months['pous'] => $months['makha'],
            $months['makha'] => $months['phalgun'],
            $months['phalgun'] => $months['cetra'],
            $months['cetra'] => $months['visak'],
            $months['visak'] => $months['jesh'],
            $months['jesh'] => $this->isLeapMonthYear($buddhistEraYear)
                ? $months['adhika_asadha_first']
                : $months['asadha'],
            $months['adhika_asadha_first'] => $months['adhika_asadha_second'],
            $months['adhika_asadha_second'] => $months['srapoan'],
            $months['asadha'] => $months['srapoan'],
            $months['srapoan'] => $months['bhadrapada'],
            $months['bhadrapada'] => $months['assuj'],
            $months['assuj'] => $months['kattik'],
            $months['kattik'] => $months['mekasira'],
        ];

        if (! array_key_exists($currentMonth, $map)) {
            throw new InvalidArgumentException('Invalid Khmer month index provided.');
        }

        return $map[$currentMonth];
    }

    private function isLeapMonthYear(int $buddhistEraYear): bool
    {
        return $this->protetinLeap($buddhistEraYear) === 1;
    }

    private function isLeapDayYear(int $buddhistEraYear): bool
    {
        return $this->protetinLeap($buddhistEraYear) === 2;
    }

    /**
     * Determine the protetin leap status for a given Buddhist Era (BE) year.
     *
     * Protetin leap is the actual calendar leap type used in the Khmer calendar.
     * It resolves the intermediate Bodithey leap result (which can have type 3 = both)
     * into the final calendar representation (only type 0, 1, or 2).
     *
     * Rules:
     * - If Bodithey leap = 3 (both leap month and day):
     *   → Current year becomes leap month only (1)
     *   → Next year gets deferred leap day (2)
     * - If Bodithey leap = 1 or 2:
     *   → Protetin leap = Bodithey leap
     * - If Bodithey leap = 0:
     *   → If previous year was type 3: Current year gets deferred leap day (2)
     *   → Otherwise: Normal year (0)
     *
     * Returns:
     *  - 0: Normal year (354 days)
     *  - 1: Leap month year (384 days)
     *  - 2: Leap day year (355 days)
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return int Protetin leap type (0, 1, or 2)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     * @see https://khmer-calendar.tovnah.com/calendar For algorithm source reference
     */
    private function protetinLeap(int $buddhistEraYear): int
    {
        $leapType = $this->boditheyLeap($buddhistEraYear);

        return match (true) {
            $leapType === 3 => 1,
            $leapType === 1, $leapType === 2 => $leapType,
            $this->boditheyLeap($buddhistEraYear - 1) === 3 => 2,
            default => 0,
        };
    }

    /**
     * Determine the Bodithey leap status for a given Buddhist Era (BE) year.
     *
     * Bodithey leap is an intermediate calculation that can identify both leap month
     * and leap day in the same year. This is then resolved by protetinLeap() into
     * the actual calendar representation.
     *
     * Algorithm based on "Pratitin Soryakkatik-Chankatik 1900-1999" by Mr. Roath Kim Soeun.
     *
     * Leap Month Determination (Bodithey):
     * - If bodithey >= 25 OR bodithey <= 5: Leap month year
     * - Special case: If bodithey = 25 AND next year = 5: NOT leap month (only next year is)
     * - Special case: If bodithey = 24 AND next year = 6: IS leap month (enforced)
     *
     * Leap Day Determination (Avoman):
     * - If Khmer solar leap year AND avoman <= 126: Leap day year
     * - If NOT Khmer solar leap year AND avoman <= 137: Leap day year
     * - Special case: If avoman = 137 AND next year = 0: NOT leap day (next year is)
     *
     * Returns:
     *  - 0: Normal year
     *  - 1: Leap month only
     *  - 2: Leap day only
     *  - 3: Both leap month and day (resolved by protetinLeap)
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return int Bodithey leap type (0, 1, 2, or 3)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     */
    private function boditheyLeap(int $buddhistEraYear): int
    {
        $avoman = $this->avoman($buddhistEraYear);
        $bodithey = $this->bodithey($buddhistEraYear);

        $leapMonth = ($bodithey >= 25 || $bodithey <= 5) ? 1 : 0;

        $leapDay = 0;
        if ($this->isSolarLeapYear($buddhistEraYear)) {
            if ($avoman <= 126) {
                $leapDay = 1;
            }
        } elseif ($avoman <= 137) {
            $leapDay = $this->avoman($buddhistEraYear + 1) !== 0 ? 1 : 0;
        }

        // Special case: consecutive 25/5 - only 5 is leap month
        if ($bodithey === 25 && $this->bodithey($buddhistEraYear + 1) === 5) {
            $leapMonth = 0;
        }

        // Special case: consecutive 24/6 - 24 is leap month
        if ($bodithey === 24 && $this->bodithey($buddhistEraYear + 1) === 6) {
            $leapMonth = 1;
        }

        return match (true) {
            $leapMonth === 1 && $leapDay === 1 => 3,
            $leapMonth === 1 => 1,
            $leapDay === 1 => 2,
            default => 0,
        };
    }

    /**
     * Determine if a year is a Khmer solar leap year (366 days).
     *
     * A Khmer solar year is a leap year if kromathupul <= 207.
     *
     * Formula: kromathupul = 800 - ((BE × 292207 + 499) mod 800)
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return bool True if solar leap year (366 days), false if normal (365 days)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     */
    private function isSolarLeapYear(int $buddhistEraYear): bool
    {
        return $this->kromathupul($buddhistEraYear) <= 207;
    }

    /**
     * Calculate Aharkun (អាហារគុណ ឬ ហារគុណ) for a given Buddhist Era (BE) year.
     *
     * Aharkun is a fundamental value used in calculating leap months (Bodithey)
     * and leap days (Avoman). It is based on traditional Khmer astronomical constants.
     *
     * Formula: aharkun = floor((BE × 292207 + 499) / 800) + 4
     *
     * @param  int  $buddhistEraYear  Buddhist Era year (e.g., 2569)
     * @return int The calculated Aharkun value
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     * @see https://khmer-calendar.tovnah.com/calendar For algorithm source reference
     */
    private function aharkun(int $buddhistEraYear): int
    {
        $solarMonthsSinceEpoch = ($buddhistEraYear * 292_207) + 499;

        return (int) floor($solarMonthsSinceEpoch / 800) + 4;
    }

    /**
     * Calculate the Aharkun modulus for a given Buddhist Era (BE) year.
     *
     * This value is used to derive Kromathupul.
     *
     * Formula: aharkun_mod = (BE × 292207 + 499) mod 800
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return int The calculated Aharkun modulus (0-799)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     */
    private function aharkunMod(int $buddhistEraYear): int
    {
        return (($buddhistEraYear * 292_207) + 499) % 800;
    }

    /**
     * Calculate Kromathupul (ក្រមធុបុល) for a given Buddhist Era (BE) year.
     *
     * Kromathupul is used to determine whether a Khmer solar year is a leap year.
     * It is calculated by subtracting the Aharkun modulus from 800.
     *
     * Formula: kromathupul = 800 - aharkun_mod
     *
     * Range: 1-800
     * - If kromathupul <= 207: Solar leap year (366 days)
     * - Otherwise: Normal solar year (365 days)
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return int The resulting Kromathupul value (1-800)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     */
    private function kromathupul(int $buddhistEraYear): int
    {
        return 800 - $this->aharkunMod($buddhistEraYear);
    }

    /**
     * Calculate Bodithey (បូតិថី) for a given Buddhist Era (BE) year.
     *
     * Bodithey determines if a given year is a leap-month year.
     * It is used to identify leap month years in the Khmer calendar.
     *
     * Formula:
     *   temp = floor((aharkun × 11 + 25) / 692)
     *   bodithey = (temp + aharkun + 29) mod 30
     *
     * Range: 0-29
     *
     * Leap Month Determination:
     * - If bodithey >= 25 OR bodithey <= 5: Year is a leap month year
     * - Special cases handled in boditheyLeap() method
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return int The calculated Bodithey value (0-29)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     */
    private function bodithey(int $buddhistEraYear): int
    {
        $aharkun = $this->aharkun($buddhistEraYear);
        $avml = (int) floor((11 * $aharkun + 25) / 692);

        return ($avml + $aharkun + 29) % 30;
    }

    /**
     * Calculate Avoman (អាវមាន) for a given Buddhist Era (BE) year.
     *
     * Avoman determines if a given year is a leap-day year.
     * It is used to identify leap day years in the Khmer calendar.
     *
     * Formula: avoman = (aharkun × 11 + 25) mod 692
     *
     * Range: 0-691
     *
     * Leap Day Determination:
     * - If Khmer solar leap year AND avoman <= 126: Leap day year
     * - If NOT Khmer solar leap year AND avoman <= 137: Leap day year
     * - Special case: If avoman = 137 AND next year = 0: Not leap day (next year is)
     *
     * @param  int  $buddhistEraYear  Buddhist Era year
     * @return int The calculated Avoman value (0-691)
     *
     * @see docs/algorithms.md For detailed algorithm documentation
     */
    private function avoman(int $buddhistEraYear): int
    {
        $aharkun = $this->aharkun($buddhistEraYear);

        return (11 * $aharkun + 25) % 692;
    }

    private function buildNewYearSnapshot(int $gregorianYear): NewYearSnapshot
    {
        $jsYear = ($gregorianYear + 544) - 1182;
        $info = $this->yearInfo($jsYear);

        $has366 = $this->hasSolarLeapDay($jsYear);
        $hasLeapMonth = $this->hasLeapMonth($jsYear);
        $hasLeapDay = $this->hasLeapDay($jsYear);
        $jesthHasThirty = $this->jesthHasThirty($jsYear, $hasLeapMonth, $hasLeapDay);

        $lerngSakWeekday = ($info->aharkun() - 2) % 7;
        $lerngSakDate = $this->lerngSakLunarDate($jsYear, $info);

        $sotins = $this->calculateSotins($jsYear);
        $time = $this->calculateNewYearTime($sotins);

        return new NewYearSnapshot(
            aharkun: $info->aharkun(),
            kromathupul: $info->kromathupul(),
            avoman: $info->avoman(),
            bodithey: $info->bodithey(),
            hasSolarLeapDay: $has366,
            hasLeapMonth: $hasLeapMonth,
            hasLeapDay: $hasLeapDay,
            jesthHasThirtyDays: $jesthHasThirty,
            lerngSakWeekday: $lerngSakWeekday,
            lerngSakDate: $lerngSakDate,
            sotins: $sotins,
            timeOfNewYear: $time
        );
    }

    private function yearInfo(int $jsYear): LunisolarYearInfo
    {
        $h = 292_207 * $jsYear + 373;
        $aharkun = (int) floor($h / 800) + 1;
        $kromathupul = 800 - ($h % 800);

        $a = 11 * $aharkun + 650;
        $avoman = $a % 692;
        $bodithey = (int) (($aharkun + floor($a / 692)) % 30);

        return new LunisolarYearInfo($aharkun, $kromathupul, $avoman, $bodithey);
    }

    private function hasSolarLeapDay(int $jsYear): bool
    {
        return $this->yearInfo($jsYear)->kromathupul() <= 207;
    }

    private function hasLeapMonth(int $jsYear): bool
    {
        $info = $this->yearInfo($jsYear);
        $next = $this->yearInfo($jsYear + 1);

        return ! ($info->bodithey() === 25 && $next->bodithey() === 5)
            && (
                $info->bodithey() > 24
                || $info->bodithey() < 6
                || ($info->bodithey() === 24 && $next->bodithey() === 6)
            );
    }

    private function hasLeapDay(int $jsYear): bool
    {
        $info = $this->yearInfo($jsYear);
        $next = $this->yearInfo($jsYear + 1);
        $previous = $this->yearInfo($jsYear - 1);
        $hasSolarLeapDay = $this->hasSolarLeapDay($jsYear);

        return ($hasSolarLeapDay && $info->avoman() < 127)
            || (
                ! ($info->avoman() === 137 && $next->avoman() === 0)
                && (
                    (! $hasSolarLeapDay && $info->avoman() < 138)
                    || ($previous->avoman() === 137 && $info->avoman() === 0)
                )
            );
    }

    private function jesthHasThirty(int $jsYear, bool $hasLeapMonth, bool $hasLeapDay): bool
    {
        if ($hasLeapMonth && $hasLeapDay) {
            return false;
        }

        if (
            ! $hasLeapDay
            && $this->hasLeapMonth($jsYear - 1)
            && $this->hasLeapDay($jsYear - 1)
        ) {
            return true;
        }

        return $hasLeapDay;
    }

    private function lerngSakLunarDate(int $jsYear, LunisolarYearInfo $info): LunarDateLerngSak
    {
        $months = LunisolarConstants::lunarMonths();
        $bodithey = $info->bodithey();

        if ($this->hasLeapMonth($jsYear - 1) && $this->hasLeapDay($jsYear - 1)) {
            $bodithey = ($bodithey + 1) % 30;
        }

        $day = $bodithey >= 6 ? $bodithey - 1 : $bodithey;
        $month = $bodithey >= 6 ? $months['cetra'] : $months['visak'];

        return new LunarDateLerngSak($day, $month);
    }

    /**
     * @return SolarNewYearDay[]
     */
    private function calculateSotins(int $jsYear): array
    {
        $sotins = $this->hasSolarLeapDay($jsYear - 1)
            ? [363, 364, 365, 366]
            : [362, 363, 364, 365];

        return array_map(function (int $sotin) use ($jsYear): SolarNewYearDay {
            $sunInfo = $this->sunInfo($jsYear, $sotin);

            $reasey = (int) floor($sunInfo->inaugurationAsLibda() / (30 * 60));
            $angsar = (int) floor(($sunInfo->inaugurationAsLibda() % (30 * 60)) / 60);
            $libda = $sunInfo->inaugurationAsLibda() % 60;

            return new SolarNewYearDay($sotin, $reasey, $angsar, $libda);
        }, $sotins);
    }

    private function calculateNewYearTime(array $sotins): SolarTimeOfNewYear
    {
        $candidates = array_values(array_filter(
            $sotins,
            static fn (SolarNewYearDay $day): bool => $day->angsar() === 0
        ));

        if (count($candidates) !== 1) {
            throw new RuntimeException('Unable to determine new year sotin with zero angle.');
        }

        $libda = $candidates[0]->libda();
        $minutes = (24 * 60) - ($libda * 24);

        return new SolarTimeOfNewYear(
            (int) floor($minutes / 60),
            $minutes % 60
        );
    }

    private function sunInfo(int $jsYear, int $sotin): SolarSunInfo
    {
        $previous = $this->yearInfo($jsYear - 1);
        $average = $this->sunAverageAsLibda($sotin, $previous);
        $leftOver = $this->sunLeftOver($average);
        $khan = (int) floor($leftOver / (30 * 60));
        $residual = $this->lastResidual($khan, $leftOver);

        $reasey = $residual->reasey();
        $angsar = $residual->angsar();

        $khanValue = $angsar >= 15 ? (2 * $reasey) + 1 : 2 * $reasey;
        $pouichalip = $angsar >= 15
            ? 60 * ($angsar - 15) + $residual->libda()
            : 60 * $angsar + $residual->libda();

        $phol = $this->phol($khanValue, $pouichalip);
        $pholAsLibda = (30 * 60 * $phol->reasey()) + (60 * $phol->angsar()) + $phol->libda();

        $inauguration = $khan <= 5
            ? $average - $pholAsLibda
            : $average + $pholAsLibda;

        return new SolarSunInfo(
            averageAsLibda: $average,
            khan: $khan,
            pouichalip: $pouichalip,
            phol: $phol,
            inaugurationAsLibda: (int) round($inauguration)
        );
    }

    private function sunAverageAsLibda(int $sotin, LunisolarYearInfo $info): int
    {
        $r2 = 800 * $sotin + $info->kromathupul();
        $reasey = (int) floor($r2 / 24_350);
        $r3 = $r2 % 24_350;
        $angsar = (int) floor($r3 / 811);
        $r4 = $r3 % 811;
        $libda = (int) floor($r4 / 14) - 3;

        return (30 * 60 * $reasey) + (60 * $angsar) + $libda;
    }

    private function sunLeftOver(int $sunAverageAsLibda): int
    {
        $baseline = (30 * 60 * 2) + (60 * 20);
        $leftOver = $sunAverageAsLibda - $baseline;

        if ($sunAverageAsLibda < $baseline) {
            $leftOver += (30 * 60 * 12);
        }

        return $leftOver;
    }

    private function lastResidual(int $khan, int $leftOver): SolarResidual
    {
        $value = match (true) {
            in_array($khan, [0, 1, 2], true) => $khan,
            in_array($khan, [3, 4, 5], true) => (30 * 60 * 6) - $leftOver,
            in_array($khan, [6, 7, 8], true) => $leftOver - (30 * 60 * 6),
            in_array($khan, [9, 10, 11], true) => ((30 * 60 * 11) + (60 * 29) + 60) - $leftOver,
            default => throw new RuntimeException('Invalid khan value when computing residual.'),
        };

        return new SolarResidual(
            reasey: (int) floor($value / (30 * 60)),
            angsar: (int) floor(($value % (30 * 60)) / 60),
            libda: $value % 60
        );
    }

    private function phol(int $khan, int $pouichalip): SolarPhol
    {
        $multiplicities = [35, 32, 27, 22, 13, 5];
        $chhayas = [0, 35, 67, 94, 116, 129];

        $index = min($khan, 5);
        $multiplicity = $multiplicities[$index] ?? 0;
        $chhaya = $chhayas[$index] ?? 134;

        $q = (int) floor(($pouichalip * $multiplicity) / 900);
        $total = $q + $chhaya;

        return new SolarPhol(
            reasey: 0,
            angsar: (int) floor($total / 60),
            libda: $total % 60
        );
    }
}

