<?php

declare(strict_types=1);

return [
    'solar' => [
        [
            'slug' => 'international_new_year',
            'month' => 1,
            'day' => 1,
            'type' => 'public',
            'title' => 'international_new_year',
        ],
        [
            'slug' => 'victory_over_genocide_regime',
            'month' => 1,
            'day' => 7,
            'type' => 'public',
            'title' => 'victory_over_genocide_regime',
        ],
        [
            'slug' => 'international_womens_day',
            'month' => 3,
            'day' => 8,
            'type' => 'public',
            'title' => 'international_womens_day',
        ],
        [
            'slug' => 'king_fathers_memorial',
            'month' => 10,
            'day' => 15,
            'type' => 'public',
            'title' => 'king_fathers_memorial',
        ],
    ],
    'lunisolar' => [
        [
            'slug' => 'khmer_new_year',
            'resolver' => 'khmer_new_year',
            'type' => 'public',
            'title' => 'khmer_new_year',
            'description' => 'khmer_new_year_description',
        ],
        [
            'slug' => 'visak_bochea',
            'resolver' => 'lunar_phase',
            'month_slug' => 'visak',
            'phase' => 'waxing',
            'day' => 15,
            'type' => 'public',
            'title' => 'visak_bochea',
            'description' => 'visak_bochea_description',
        ],
        [
            'slug' => 'pchum_ben',
            'resolver' => 'pchum_ben',
            'type' => 'public',
            'title' => 'pchum_ben',
            'description' => 'pchum_ben_description',
        ],
        [
            'slug' => 'meak_bochea',
            'resolver' => 'lunar_phase',
            'month_slug' => 'makha',
            'phase' => 'waxing',
            'day' => 15,
            'type' => 'public',
            'title' => 'meak_bochea',
            'description' => 'meak_bochea_description',
        ],
        [
            'slug' => 'chinese_new_year',
            'resolver' => 'lunar_phase',
            'month_slug' => 'makha',
            'phase' => 'waxing',
            'day' => 1,
            'type' => 'public',
            'title' => 'chinese_new_year',
            'description' => 'chinese_new_year_description',
        ],
    ],
];

