<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;

final class LunisolarCalculator
{
    private const EPOCH_DATE = '1900-01-01';

    /** @var array<string, LunarPosition> */
    private array $positionCache = [];

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
        $snapshot = $this->buildNewYearSnapshot($gregorianYear);

        $time = $snapshot->timeOfNewYear();
        $hour = $time->hour();
        $minute = $time->minute();

        $base = CarbonImmutable::create($gregorianYear, 4, 17, $hour, $minute, 0, LunisolarConstants::TIMEZONE);

        $lerngSak = $snapshot->lerngSakDate();
        $position = $this->findLunarPosition($base);

        $currentOrdinal = (($position->month() - 4) * 30) + $position->day();
        $lerngSakOrdinal = (($lerngSak->month() - 4) * 30) + $lerngSak->day();
        $daysDifference = $currentOrdinal - $lerngSakOrdinal;

        $firstSotin = $snapshot->sotins()[0] ?? null;

        if (! $firstSotin instanceof SolarNewYearDay) {
            throw new RuntimeException('Failed to determine sotin sequence.');
        }

        $newYearDays = $firstSotin->angsar() === 0 ? 4 : 3;

        return $base->subDays($daysDifference + $newYearDays - 1);
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

        if ($bodithey === 25 && $this->bodithey($buddhistEraYear + 1) === 5) {
            $leapMonth = 0;
        }

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

    private function isSolarLeapYear(int $buddhistEraYear): bool
    {
        return $this->kromathupul($buddhistEraYear) <= 207;
    }

    private function aharkun(int $buddhistEraYear): int
    {
        $solarMonthsSinceEpoch = ($buddhistEraYear * 292_207) + 499;

        return (int) floor($solarMonthsSinceEpoch / 800) + 4;
    }

    private function aharkunMod(int $buddhistEraYear): int
    {
        return (($buddhistEraYear * 292_207) + 499) % 800;
    }

    private function kromathupul(int $buddhistEraYear): int
    {
        return 800 - $this->aharkunMod($buddhistEraYear);
    }

    private function bodithey(int $buddhistEraYear): int
    {
        $aharkun = $this->aharkun($buddhistEraYear);
        $avml = (int) floor((11 * $aharkun + 25) / 692);

        return ($avml + $aharkun + 29) % 30;
    }

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

