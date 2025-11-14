<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

/**
 * Registry of the seven New Year angels based on day of week.
 *
 * According to Khmer folklore (Songkran Sote), each year one of seven
 * angels comes down to earth on Songkran day, depending on which day
 * of the week Songkran falls on.
 */
final class NewYearAngels
{
    /**
     * @var array<int, NewYearAngel>
     */
    private static array $angels = [];

    /**
     * Get the New Year angel for a specific day of week.
     *
     * @param  int  $dayOfWeek  Day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
     * @return NewYearAngel
     */
    public static function getAngelForDay(int $dayOfWeek): NewYearAngel
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException("Day of week must be 0-6, got: {$dayOfWeek}");
        }

        $angels = self::getAllAngels();

        return $angels[$dayOfWeek];
    }

    /**
     * Get the New Year angel for a specific year based on Songkran day.
     *
     * @param  int  $gregorianYear  Gregorian year
     * @param  SongkranCalculator  $calculator  Songkran calculator instance
     * @return NewYearAngel
     */
    public static function getAngelForYear(int $gregorianYear, SongkranCalculator $calculator): NewYearAngel
    {
        $leungsak = $calculator->getLeungsak($gregorianYear);
        $dayOfWeek = $leungsak[2]; // Pea (day of week)

        return self::getAngelForDay($dayOfWeek);
    }

    /**
     * Get all seven New Year angels.
     *
     * @return array<int, NewYearAngel>
     */
    public static function getAllAngels(): array
    {
        if (self::$angels !== []) {
            return self::$angels;
        }

        // Sunday (0): Tungsa Tevy
        self::$angels[0] = new NewYearAngel(
            dayOfWeek: 0,
            nameKey: 'tungsa_tevy',
            jewelryKey: 'ruby_necklace',
            flowerKey: 'pomegranate_flower',
            foodKey: 'fig_fruit',
            rightHandKey: 'disc_of_power',
            leftHandKey: 'shell',
            animalKey: 'garuda'
        );

        // Monday (1): Koreak Tevy
        self::$angels[1] = new NewYearAngel(
            dayOfWeek: 1,
            nameKey: 'koreak_tevy',
            jewelryKey: 'pearls',
            flowerKey: 'ankeabos_flower',
            foodKey: 'oil',
            rightHandKey: 'sword',
            leftHandKey: 'cane',
            animalKey: 'tiger'
        );

        // Tuesday (2): Reaksa Tevy
        self::$angels[2] = new NewYearAngel(
            dayOfWeek: 2,
            nameKey: 'reaksa_tevy',
            jewelryKey: 'precious_stones',
            flowerKey: 'lotus_flower',
            foodKey: 'blood',
            rightHandKey: 'trident',
            leftHandKey: 'bow',
            animalKey: 'horse'
        );

        // Wednesday (3): Mondar Tevy
        self::$angels[3] = new NewYearAngel(
            dayOfWeek: 3,
            nameKey: 'mondar_tevy',
            jewelryKey: 'cats_eye_gemstones',
            flowerKey: 'fragrant_flower',
            foodKey: 'milk',
            rightHandKey: 'needle',
            leftHandKey: 'cane',
            animalKey: 'donkey'
        );

        // Thursday (4): Keriny Tevy
        self::$angels[4] = new NewYearAngel(
            dayOfWeek: 4,
            nameKey: 'keriny_tevy',
            jewelryKey: 'emerald',
            flowerKey: 'mondea_flower',
            foodKey: 'beans_and_sesames',
            rightHandKey: 'harpoon',
            leftHandKey: 'gun',
            animalKey: 'elephant'
        );

        // Friday (5): Kemira Tevy
        self::$angels[5] = new NewYearAngel(
            dayOfWeek: 5,
            nameKey: 'kemira_tevy',
            jewelryKey: 'precious_gems',
            flowerKey: 'violet_flower',
            foodKey: 'banana',
            rightHandKey: 'sword',
            leftHandKey: 'mandolin',
            animalKey: 'water_buffalo'
        );

        // Saturday (6): Mohurea Tevy
        self::$angels[6] = new NewYearAngel(
            dayOfWeek: 6,
            nameKey: 'mohurea_tevy',
            jewelryKey: 'sapphires',
            flowerKey: 'trokeat_flower',
            foodKey: 'deer_meat',
            rightHandKey: 'disc_of_power',
            leftHandKey: 'trident',
            animalKey: 'peacock'
        );

        return self::$angels;
    }
}

