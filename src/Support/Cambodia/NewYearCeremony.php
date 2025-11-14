<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

/**
 * Information about Khmer New Year ceremonies and traditions.
 *
 * Based on traditional Khmer New Year practices including:
 * - Daily ceremonies (Songkran, Vonabot, Leungsak)
 * - Building of Sand Hill ceremony (Vealokchetei)
 * - Traditional games
 * - Folklore stories
 */
final class NewYearCeremony
{
    /**
     * Get ceremony activities for Day 1 (Songkran).
     *
     * @return array<string, string>
     */
    public static function songkranDay(): array
    {
        return [
            'morning' => 'food_offering_to_temple',
            'afternoon' => 'build_sand_hill',
            'evening' => 'offer_drinks_to_monks',
        ];
    }

    /**
     * Get ceremony activities for Day 2 (Vonabot).
     *
     * @return array<string, string>
     */
    public static function vonabotDay(): array
    {
        return [
            'morning' => 'give_to_parents',
            'afternoon' => 'sand_hill_prayer',
            'evening' => 'bangskole_ceremony',
        ];
    }

    /**
     * Get ceremony activities for Day 3 (Leungsak).
     *
     * @return array<string, string>
     */
    public static function leungsakDay(): array
    {
        return [
            'morning' => 'complete_sand_hill',
            'afternoon' => 'bathing_ceremony',
            'evening' => 'buddha_bathing',
        ];
    }

    /**
     * Get information about Building of Sand Hill ceremony (Vealokchetei).
     *
     * @return array<string, mixed>
     */
    public static function sandHillCeremony(): array
    {
        return [
            'name_key' => 'vealokchetei',
            'symbolism' => 'stupa_cholamony_chaetdei',
            'structure' => [
                'center' => 'mount_meru',
                'surrounding' => 'seven_mountains',
                'fence' => 'reajevat',
                'altars' => 'eight_directions',
            ],
            'significance' => 'merit_and_relief_from_misfortune',
        ];
    }

    /**
     * Get traditional games played during New Year.
     *
     * @return array<string, string>
     */
    public static function traditionalGames(): array
    {
        return [
            'chhoung' => 'chhoung_game',
            'teang_prot' => 'tug_of_war',
            'ongkunh' => 'ongkunh_game',
            'leak_konsaeng' => 'hide_and_seek',
            'donderm' => 'donderm_game',
            'sluk_chaue' => 'sluk_chaue_game',
        ];
    }

    /**
     * Get preparation items for New Year ceremonies.
     *
     * @return array<string, mixed>
     */
    public static function preparationItems(): array
    {
        return [
            'baysei' => ['count' => 2, 'description' => 'banana_trunk_decorated'],
            'slathor' => ['count' => 2, 'description' => 'decoration_stand'],
            'areca_palms' => ['count' => 5, 'description' => 'dried_areca_palms'],
            'betel_leaves' => ['count' => 5, 'description' => 'betel_leaves'],
            'incenses' => ['count' => 5, 'description' => 'incenses'],
            'candles' => ['count' => 5, 'description' => 'candles'],
            'perfumes' => ['count' => 2, 'description' => 'bottles_of_perfume'],
            'flowers' => ['count' => 1, 'description' => 'flowers'],
            'cigarettes' => ['count' => 1, 'description' => 'cigarettes'],
            'rice' => ['count' => 1, 'description' => 'rice'],
            'water_drinks' => ['count' => 1, 'description' => 'water_and_drinks'],
            'cake_fruit' => ['count' => 1, 'description' => 'cake_and_fresh_fruit'],
        ];
    }
}

