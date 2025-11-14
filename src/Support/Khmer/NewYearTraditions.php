<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Khmer;

/**
 * Khmer New Year traditions and cultural practices.
 *
 * Contains information about traditional practices, beliefs, and
 * cultural significance of Khmer New Year celebrations.
 */
final class NewYearTraditions
{
    /**
     * Get the Songkran Sote folklore story summary.
     *
     * This is the story of Dhammabal Koma and Kobel Mohaprom,
     * which explains the origin of the seven New Year angels.
     *
     * @return array<string, string>
     */
    public static function songkranSoteStory(): array
    {
        return [
            'title_key' => 'songkran_sote',
            'alternative_names_key' => 'dhommabal_kabel_moha_prom',
            'summary_key' => 'songkran_sote_summary',
            'characters' => [
                'dhammabal_koma' => 'wise_son',
                'kobel_mohaprom' => 'king_of_heaven',
                'seven_daughters' => 'new_year_angels',
            ],
            'significance_key' => 'angel_origin_story',
        ];
    }

    /**
     * Get Sand Hill folktales.
     *
     * @return array<string, array<string, string>>
     */
    public static function sandHillFolktales(): array
    {
        return [
            'sailor_story' => [
                'title_key' => 'sailor_sand_hill_story',
                'summary_key' => 'sailor_story_summary',
            ],
            'komjil_ksach' => [
                'title_key' => 'lazy_sand_boy_story',
                'summary_key' => 'komjil_ksach_summary',
            ],
            'fisherman_story' => [
                'title_key' => 'fisherman_sand_hill_story',
                'summary_key' => 'fisherman_story_summary',
            ],
        ];
    }

    /**
     * Get cultural beliefs and practices.
     *
     * @return array<string, string>
     */
    public static function beliefs(): array
    {
        return [
            'cleansing' => 'cleanse_body_soul',
            'new_clothes' => 'wear_new_clothes',
            'clean_home' => 'clean_and_decorate_home',
            'welcome_angel' => 'welcome_new_year_angel',
            'good_deeds' => 'perform_good_deeds',
            'bathing_significance' => 'bathing_good_deeds',
            'free_animals' => 'free_animals_for_longevity',
        ];
    }

    /**
     * Get historical context about New Year dates.
     *
     * @return array<string, string>
     */
    public static function historicalContext(): array
    {
        return [
            'pre_angkor' => 'first_month_mekasay',
            'post_angkor' => 'april_new_year',
            'zhou_daguan' => 'chinese_traveler_13th_century',
            'civil_reasons' => 'farming_season_completion',
            'ancient_duration' => 'month_long_celebration',
            'modern_duration' => 'three_to_four_days',
        ];
    }
}

